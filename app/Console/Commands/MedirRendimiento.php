<?php

namespace App\Console\Commands;

use App\Support\CodigoBarras;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Prueba de rendimiento: importa la misma lista de códigos con la versión
 * original y con la optimizada, y compara tiempo y memoria. Genera:
 *  - resultados.csv        tiempo y memoria pico de cada versión
 *  - memoria-*.csv         memoria a lo largo del tiempo (equivalente a mprof)
 *  - perfiles/cachegrind.* perfil de funciones con Xdebug (equivalente a cProfile;
 *                          se abre con Webgrind o KCachegrind, equivalentes a SnakeViz)
 *
 *   php artisan rendimiento:medir --url=https://world.openfoodfacts.net
 */
class MedirRendimiento extends Command
{
    protected $signature = 'rendimiento:medir
        {--url= : API contra la que se mide (p. ej. https://world.openfoodfacts.net); sin ella se usa el servidor simulado}
        {--latencia=250 : Latencia en ms del servidor simulado}
        {--codigos= : Lista de códigos a importar (por defecto: códigos reales descargados de la API con --url, o tools/rendimiento/codigos.txt con el servidor simulado)}
        {--cantidad=60 : Con --url, cuántos códigos reales descargar (la API permite ~100 consultas de producto por minuto)}
        {--pausa=60 : Con --url, segundos de espera entre corridas para no exceder el límite de la API}
        {--salida=docs/rendimiento : Carpeta de resultados}
        {--xdebug= : Ruta de xdebug.so / php_xdebug.dll si Xdebug no está activado en php.ini}';

    protected $description = 'Compara el rendimiento de inventario:importar antes y después de la optimización';

    private const VERSIONES = ['antes' => ['--sin-optimizar'], 'despues' => []];

    private array $entorno;

    private ?Process $servidor = null;

    /** Momento en que terminó la última corrida (para la pausa entre corridas). */
    private ?float $ultimaCorrida = null;

    public function handle(): int
    {
        $salida = base_path($this->option('salida'));
        $url = $this->option('url') ? rtrim($this->option('url'), '/') : null;

        $codigos = match (true) {
            (bool) $this->option('codigos') => is_file($this->option('codigos')) ? $this->option('codigos') : base_path($this->option('codigos')),
            $url !== null => $this->descargarCodigosReales($url),
            default => base_path('tools/rendimiento/codigos.txt'),
        };

        if (! $codigos || ! is_file($codigos)) {
            $codigos && $this->error("No encontré la lista de códigos «{$codigos}».");

            return self::FAILURE;
        }
        @mkdir("{$salida}/perfiles", 0777, true);

        // Base de datos aparte: la medición no toca los datos del restaurante
        $baseDatos = database_path('rendimiento.sqlite');
        touch($baseDatos);
        $this->entorno = [
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $baseDatos,
            'CACHE_STORE' => 'file', 'LOG_CHANNEL' => 'null',
            'OPENFOODFACTS_URL' => $url ?: $this->iniciarServidorSimulado(),
        ];
        if (! $this->entorno['OPENFOODFACTS_URL']) {
            return self::FAILURE;
        }
        $this->info("API: {$this->entorno['OPENFOODFACTS_URL']}");

        try {
            // 1. Tiempo, memoria pico y memoria en el tiempo (sin Xdebug, que alenta el proceso)
            $resultados = [];
            foreach (self::VERSIONES as $version => $opciones) {
                $this->reiniciar();
                $this->line("  midiendo «{$version}»…");
                $resultados[$version] = $this->importar($codigos, [...$opciones, "--memoria={$salida}/memoria-{$version}.csv"]);

                if ($resultados[$version]['guardados'] === 0) {
                    $this->error("La versión «{$version}» no guardó ningún ingrediente: la medición no sería válida.");
                    $this->line("No encontrados: {$resultados[$version]['no_encontrados']} · errores de conexión: {$resultados[$version]['errores']}.");
                    $this->line('Revisa la conexión (php artisan openfoodfacts:diagnosticar) o usa una lista de códigos que existan en la API.');

                    return self::FAILURE;
                }
            }
            $this->guardarResultados("{$salida}/resultados.csv", $resultados);
            $this->mostrarComparacion($resultados);

            // 2. Perfil de funciones con Xdebug
            $this->perfilar($codigos, "{$salida}/perfiles");
        } finally {
            $this->servidor?->stop();
        }

        $this->info("Resultados en {$this->option('salida')}/");

        return self::SUCCESS;
    }

    /** @return array{segundos: float, memoria_pico_mb: float, guardados: int, no_encontrados: int, errores: int} */
    private function importar(string $codigos, array $opciones = [], array $php = []): array
    {
        $this->pausar();
        $proceso = new Process([PHP_BINARY, ...$php, 'artisan', 'inventario:importar', $codigos, '--json', ...$opciones], base_path(), $this->entorno, null, 1800);
        $proceso->run(); // termina con código 1 si no guardó nada: eso se revisa con el resumen JSON
        $this->ultimaCorrida = microtime(true);

        $lineas = array_filter(explode("\n", trim($proceso->getOutput())));
        $resumen = json_decode((string) end($lineas), true);

        if (! is_array($resumen)) {
            throw new \RuntimeException("La importación falló antes de terminar:\n".trim($proceso->getErrorOutput() ?: $proceso->getOutput()));
        }

        return $resumen;
    }

    /**
     * Descarga de la propia API una lista de códigos que sí existen, y le agrega
     * 5 repetidos y 3 mal escritos (como la lista del servidor simulado).
     */
    private function descargarCodigosReales(string $url): ?string
    {
        $cantidad = max(10, (int) $this->option('cantidad'));
        $this->line("Descargando {$cantidad} códigos reales de {$url}…");

        try {
            $respuesta = Http::withUserAgent(config('services.openfoodfacts.user_agent'))
                ->timeout(30)
                ->get("{$url}/api/v2/search", ['fields' => 'code', 'page_size' => $cantidad * 2]);
            $productos = $respuesta->throw()->json('products', []);
        } catch (Throwable $e) {
            $this->error('No se pudo obtener la lista de códigos de la API: '.$e->getMessage());
            $this->line('Prueba primero: php artisan openfoodfacts:diagnosticar');

            return null;
        }

        $validos = [];
        foreach ($productos as $producto) {
            try {
                $validos[] = CodigoBarras::validar((string) ($producto['code'] ?? ''));
            } catch (Throwable) {
                // la base pública tiene algunos códigos internos que no son GTIN: se ignoran
            }
        }
        $validos = array_slice(array_values(array_unique($validos)), 0, $cantidad);

        if (count($validos) < 10) {
            $this->error('La API devolvió muy pocos códigos válidos ('.count($validos).'). Intenta de nuevo en un minuto.');

            return null;
        }

        $ruta = base_path('tools/rendimiento/codigos-api.txt');
        file_put_contents($ruta, implode("\n", [
            '# Códigos reales descargados de '.$url.' el '.now()->format('Y-m-d H:i'),
            '# '.count($validos).' distintos + 5 repetidos + 3 mal escritos',
            ...$validos,
            ...array_slice($validos, 0, 5),
            '75010553632', 'ABC7501055363', '7501055363279',
        ])."\n");

        $this->info('Lista guardada en tools/rendimiento/codigos-api.txt ('.count($validos).' códigos reales).');

        return $ruta;
    }

    /**
     * Con la API real, entre una corrida y la siguiente pasan al menos --pausa segundos,
     * para no exceder su límite de consultas por minuto.
     */
    private function pausar(): void
    {
        if ($this->servidor !== null || ! $this->option('url') || $this->ultimaCorrida === null) {
            return;
        }

        $faltan = (int) ceil((int) $this->option('pausa') - (microtime(true) - $this->ultimaCorrida));
        if ($faltan > 0) {
            $this->line("  esperando {$faltan} s para no exceder el límite de consultas de la API…");
            sleep($faltan);
        }
    }

    /** Base vacía y caché vacía, para que las dos versiones empiecen igual. */
    private function reiniciar(): void
    {
        foreach ([['migrate:fresh', '--force'], ['cache:clear']] as $comando) {
            (new Process([PHP_BINARY, 'artisan', ...$comando, '-q'], base_path(), $this->entorno))->mustRun();
        }
    }

    private function perfilar(string $codigos, string $carpeta): void
    {
        $xdebug = $this->option('xdebug');
        $activo = str_contains((new Process([PHP_BINARY, '-m']))->mustRun()->getOutput(), 'xdebug');

        if (! $activo && ! $xdebug) {
            $this->warn('Xdebug no está instalado: se omiten los perfiles (ver docs/rendimiento/README.md).');

            return;
        }

        foreach (glob("{$carpeta}/cachegrind.out.*") as $viejo) {
            unlink($viejo);
        }

        foreach (self::VERSIONES as $version => $opciones) {
            $this->reiniciar();
            $this->line("  perfilando «{$version}» con Xdebug…");
            $this->importar($codigos, $opciones, [
                ...($activo ? [] : ['-d', "zend_extension={$xdebug}"]),
                '-d', 'xdebug.mode=profile', '-d', 'xdebug.start_with_request=yes', '-d', 'xdebug.use_compression=0',
                '-d', "xdebug.output_dir={$carpeta}", '-d', "xdebug.profiler_output_name=cachegrind.out.{$version}",
            ]);
        }
        $this->info('Perfiles de Xdebug: cachegrind.out.antes y cachegrind.out.despues');
    }

    private function iniciarServidorSimulado(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('En Windows el servidor integrado de PHP atiende una petición a la vez, así que no sirve para medir consultas en paralelo.');
            $this->line('Mide contra la API real de pruebas: php artisan rendimiento:medir --url=https://world.openfoodfacts.net');

            return null;
        }

        $this->servidor = new Process(
            [PHP_BINARY, '-S', '127.0.0.1:8099', base_path('tools/rendimiento/servidor-simulado.php')],
            base_path(), ['PHP_CLI_SERVER_WORKERS' => '32', 'LATENCIA_MS' => (string) (int) $this->option('latencia')],
        );
        $this->servidor->disableOutput(); // el servidor escribe una línea por petición: sin esto el búfer se llena y se bloquea
        $this->servidor->start();
        usleep(800_000);

        return 'http://127.0.0.1:8099';
    }

    private function mostrarComparacion(array $r): void
    {
        $this->newLine();
        $this->table(['Versión', 'Tiempo', 'Memoria pico', 'Ingredientes guardados'], [
            ['Antes', number_format($r['antes']['segundos'], 2).' s', number_format($r['antes']['memoria_pico_mb'], 1).' MB', $r['antes']['guardados']],
            ['Después', number_format($r['despues']['segundos'], 2).' s', number_format($r['despues']['memoria_pico_mb'], 1).' MB', $r['despues']['guardados']],
        ]);
        $this->info(sprintf('Mejora: %.1f× más rápido.', $r['antes']['segundos'] / max($r['despues']['segundos'], 0.0001)));
    }

    private function guardarResultados(string $ruta, array $resultados): void
    {
        $f = fopen($ruta, 'w');
        fputcsv($f, ['version', 'segundos', 'memoria_pico_mb', 'guardados'], escape: '');
        foreach ($resultados as $version => $r) {
            fputcsv($f, [$version, $r['segundos'], $r['memoria_pico_mb'], $r['guardados']], escape: '');
        }
        fclose($f);
    }
}
