<?php

namespace App\Console\Commands;

use App\Exceptions\OpenFoodFacts\CodigoBarrasInvalido;
use App\Exceptions\OpenFoodFacts\OpenFoodFactsException;
use App\Exceptions\OpenFoodFacts\ProductoNoEncontrado;
use App\Models\Ingrediente;
use App\Services\OpenFoodFacts\OpenFoodFactsClient;
use App\Services\OpenFoodFacts\ProductoEnvasado;
use App\Support\CodigoBarras;
use Illuminate\Console\Command;

/**
 * Importa ingredientes envasados desde una lista de códigos de barras.
 *
 *   php artisan inventario:importar codigos.txt
 *   php artisan inventario:importar codigos.txt --sin-optimizar --memoria=antes.csv
 */
class ImportarInventario extends Command
{
    protected $signature = 'inventario:importar
        {archivo : Archivo de texto con un código de barras por línea (las líneas con # se ignoran)}
        {--existencias=0 : Existencias iniciales de los ingredientes nuevos}
        {--sin-optimizar : Usa la versión original (secuencial, sin caché, producto completo) para comparar rendimiento}
        {--memoria= : Ruta de un CSV donde guardar el uso de memoria en el tiempo}
        {--json : Imprime al final una línea JSON con el resumen (la usa rendimiento:medir)}';

    protected $description = 'Importa al inventario ingredientes envasados consultando Open Food Facts por código de barras';

    /** Tamaño de cada lote en la versión optimizada. */
    private const LOTE = 50;

    private float $inicio;

    /** @var resource|null */
    private $csvMemoria = null;

    private array $conteo = ['guardados' => 0, 'no_encontrados' => 0, 'invalidos' => 0, 'errores' => 0];

    private array $problemas = [];

    /** Códigos ya guardados en esta importación (para contar ingredientes distintos). */
    private array $guardados = [];

    public function handle(OpenFoodFactsClient $cliente): int
    {
        $archivo = (string) $this->argument('archivo');
        if (! is_file($archivo) || ! is_readable($archivo)) {
            $this->error("No encontré el archivo «{$archivo}» o no se puede leer.");

            return self::FAILURE;
        }

        $existencias = $this->option('existencias');
        if (! is_numeric($existencias) || $existencias < 0) {
            $this->error('--existencias debe ser un número mayor o igual a 0.');

            return self::FAILURE;
        }

        $codigos = array_values(array_filter(
            array_map('trim', file($archivo, FILE_IGNORE_NEW_LINES)),
            fn (string $linea) => $linea !== '' && ! str_starts_with($linea, '#'),
        ));

        if ($codigos === []) {
            $this->error('El archivo no tiene códigos de barras.');

            return self::FAILURE;
        }

        $this->abrirCsvMemoria();
        $this->inicio = microtime(true);
        $this->muestrearMemoria('inicio');

        $optimizar = ! $this->option('sin-optimizar');
        $this->info(sprintf('Importando %d códigos (%s)…', count($codigos), $optimizar ? 'versión optimizada' : 'versión sin optimizar'));

        $optimizar
            ? $this->importarOptimizado($cliente, $codigos, (float) $existencias)
            : $this->importarSinOptimizar($cliente, $codigos, (float) $existencias);

        $segundos = microtime(true) - $this->inicio;
        $this->conteo['guardados'] = count($this->guardados);
        $this->muestrearMemoria('fin');
        $this->csvMemoria && fclose($this->csvMemoria);

        $this->mostrarResumen($segundos);

        if ($this->option('json')) {
            $this->line(json_encode($this->conteo + [
                'segundos' => round($segundos, 4),
                'memoria_pico_mb' => round(memory_get_peak_usage() / 1048576, 2),
            ]));
        }

        return $this->conteo['guardados'] > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * ANTES: un código a la vez, esperando cada respuesta, sin caché, descargando
     * el producto completo y guardando fila por fila.
     */
    private function importarSinOptimizar(OpenFoodFactsClient $cliente, array $codigos, float $existencias): void
    {
        foreach ($codigos as $entrada) {
            try {
                $producto = $cliente->buscarSinOptimizar($entrada);
                $ingrediente = Ingrediente::firstOrNew(['codigo_barras' => $producto->codigo]);
                $ingrediente->fill(Ingrediente::datosDesde($producto));
                if (! $ingrediente->exists) {
                    $ingrediente->fill(['existencias' => $existencias, 'unidad' => 'pieza']);
                }
                $ingrediente->save();
                $this->guardados[$producto->codigo] = true;
            } catch (OpenFoodFactsException $e) {
                $this->registrarProblema($entrada, $e);
            }

            $this->muestrearMemoria('producto');
        }
    }

    /**
     * DESPUÉS: códigos validados y sin repetir, consultas en paralelo por lotes,
     * sólo los campos necesarios, caché y un solo INSERT … ON CONFLICT por lote.
     */
    private function importarOptimizado(OpenFoodFactsClient $cliente, array $codigos, float $existencias): void
    {
        // Los repetidos se quitan de toda la lista (no sólo dentro de cada lote)
        $codigos = array_values(array_unique(array_map(CodigoBarras::normalizar(...), $codigos)));

        foreach (array_chunk($codigos, self::LOTE) as $lote) {
            $filas = [];

            foreach ($cliente->buscarVarios($lote) as $codigo => $resultado) {
                if ($resultado instanceof ProductoEnvasado) {
                    $filas[] = $this->filaParaUpsert($resultado, $existencias);
                } else {
                    $this->registrarProblema((string) $codigo, $resultado);
                }
                $this->muestrearMemoria('producto');
            }

            if ($filas !== []) {
                // Los ingredientes que ya existían conservan sus existencias
                Ingrediente::upsert($filas, ['codigo_barras'], [
                    'nombre', 'marca', 'presentacion', 'ingredientes', 'alergenos',
                    'nutriscore', 'nutrimentos', 'imagen_url', 'origen', 'updated_at',
                ]);
                foreach ($filas as $fila) {
                    $this->guardados[$fila['codigo_barras']] = true;
                }
            }

            $this->muestrearMemoria('lote');
        }
    }

    private function filaParaUpsert(ProductoEnvasado $producto, float $existencias): array
    {
        $datos = Ingrediente::datosDesde($producto);
        $ahora = now();

        // upsert() no aplica los casts del modelo: los arreglos se guardan como JSON a mano
        return array_merge($datos, [
            'alergenos' => json_encode($datos['alergenos'], JSON_UNESCAPED_UNICODE),
            'nutrimentos' => json_encode($datos['nutrimentos']),
            'existencias' => $existencias,
            'unidad' => 'pieza',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    private function registrarProblema(string $codigo, OpenFoodFactsException $e): void
    {
        $tipo = match (true) {
            $e instanceof CodigoBarrasInvalido => 'invalidos',
            $e instanceof ProductoNoEncontrado => 'no_encontrados',
            default => 'errores',
        };
        $this->conteo[$tipo]++;
        $this->problemas[] = [$codigo, $e->getMessage()];
    }

    private function mostrarResumen(float $segundos): void
    {
        if ($this->problemas !== []) {
            $this->newLine();
            $this->warn('Códigos que no se importaron:');
            $this->table(['Código', 'Motivo'], array_slice($this->problemas, 0, 50));
            if (count($this->problemas) > 50) {
                $this->line('… y '.(count($this->problemas) - 50).' más.');
            }
        }

        $this->newLine();
        $this->table(['Guardados', 'No encontrados', 'Códigos inválidos', 'Errores de conexión', 'Tiempo', 'Memoria pico'], [[
            $this->conteo['guardados'],
            $this->conteo['no_encontrados'],
            $this->conteo['invalidos'],
            $this->conteo['errores'],
            number_format($segundos, 2).' s',
            number_format(memory_get_peak_usage() / 1048576, 1).' MB',
        ]]);
    }

    private function abrirCsvMemoria(): void
    {
        $ruta = $this->option('memoria');
        if (! $ruta) {
            return;
        }

        $this->csvMemoria = fopen($ruta, 'w') ?: null;
        if (! $this->csvMemoria) {
            $this->warn("No se pudo crear {$ruta}; se continúa sin registrar memoria.");

            return;
        }
        fputcsv($this->csvMemoria, ['segundos', 'memoria_mb', 'pico_mb', 'etapa'], escape: '');
    }

    /** Equivalente a mprof: una muestra de memoria con su tiempo transcurrido. */
    private function muestrearMemoria(string $etapa): void
    {
        if (! $this->csvMemoria) {
            return;
        }

        fputcsv($this->csvMemoria, [
            round(microtime(true) - $this->inicio, 4),
            round(memory_get_usage() / 1048576, 3),
            round(memory_get_peak_usage() / 1048576, 3),
            $etapa,
        ], escape: '');
    }
}
