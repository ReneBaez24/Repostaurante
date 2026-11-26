<?php

namespace App\Services\OpenFoodFacts;

use App\Exceptions\OpenFoodFacts\OpenFoodFactsException;
use App\Exceptions\OpenFoodFacts\ProductoNoEncontrado;
use App\Exceptions\OpenFoodFacts\ServicioNoDisponible;
use App\Support\CodigoBarras;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consulta de ingredientes envasados en Open Food Facts por código de barras.
 * Documentación: https://openfoodfacts.github.io/openfoodfacts-server/api/
 */
class OpenFoodFactsClient
{
    private const NO_ENCONTRADO = '__no_encontrado__';

    private string $baseUrl;

    private string $userAgent;

    private int $timeout;

    private int $cacheMinutos;

    private int $concurrencia;

    public function __construct(?array $config = null)
    {
        $config ??= config('services.openfoodfacts', []);

        $this->baseUrl = rtrim($config['base_url'] ?? 'https://world.openfoodfacts.org', '/');
        $this->userAgent = $config['user_agent'] ?? 'PunkBrothers/1.0 (contacto@punkbrothers.com)';
        $this->timeout = (int) ($config['timeout'] ?? 8);
        $this->cacheMinutos = (int) ($config['cache_minutos'] ?? 1440);
        $this->concurrencia = max(1, (int) ($config['concurrencia'] ?? 10));
    }

    /**
     * Busca un producto. Valida el código antes de llamar a la API y guarda
     * el resultado en caché para no repetir consultas.
     *
     * @throws OpenFoodFactsException con un mensaje listo para mostrarse
     */
    public function buscar(string $codigo): ProductoEnvasado
    {
        $codigo = CodigoBarras::validar($codigo);

        if (($enCache = $this->desdeCache($codigo)) !== null) {
            return $enCache;
        }

        try {
            $respuesta = $this->peticion()->get($this->url($codigo), ['fields' => implode(',', ProductoEnvasado::CAMPOS)]);
        } catch (ConnectionException $e) {
            throw $this->sinConexion($e);
        }

        return $this->guardarEnCache($codigo, fn () => $this->interpretar($codigo, $respuesta));
    }

    /**
     * Busca varios productos a la vez (peticiones en paralelo, en grupos).
     * Nunca lanza excepción: cada código queda con su producto o con su error.
     *
     * @param  string[]  $codigos
     * @return array<string, ProductoEnvasado|OpenFoodFactsException> indexado por el código (limpio si era válido)
     */
    public function buscarVarios(array $codigos): array
    {
        $resultados = [];
        $pendientes = [];

        foreach ($codigos as $entrada) {
            try {
                $codigo = CodigoBarras::validar((string) $entrada);
            } catch (OpenFoodFactsException $e) {
                $resultados[(string) $entrada] = $e;

                continue;
            }

            if (isset($resultados[$codigo]) || isset($pendientes[$codigo])) {
                continue; // repetido en la lista
            }

            try {
                $enCache = $this->desdeCache($codigo);
            } catch (ProductoNoEncontrado $e) {
                $resultados[$codigo] = $e;

                continue;
            }

            $enCache ? $resultados[$codigo] = $enCache : $pendientes[$codigo] = true;
        }

        foreach (array_chunk(array_keys($pendientes), $this->concurrencia) as $grupo) {
            $grupo = array_map('strval', $grupo);
            $respuestas = Http::pool(fn (Pool $pool) => array_map(
                fn (string $codigo) => $this->configurar($pool->as($codigo))
                    ->get($this->url($codigo), ['fields' => implode(',', ProductoEnvasado::CAMPOS)]),
                $grupo,
            ));

            foreach ($grupo as $codigo) {
                $respuesta = $respuestas[$codigo] ?? null;

                try {
                    if (! $respuesta instanceof Response) {
                        throw $this->sinConexion($respuesta instanceof Throwable ? $respuesta : null);
                    }
                    $resultados[$codigo] = $this->guardarEnCache($codigo, fn () => $this->interpretar($codigo, $respuesta));
                } catch (OpenFoodFactsException $e) {
                    $resultados[$codigo] = $e;
                }
            }
        }

        return $resultados;
    }

    /**
     * Versión original, ANTES de la mejora de rendimiento: sin caché y descargando
     * el producto completo (todos sus campos). Se conserva sólo para poder repetir
     * la comparación (php artisan inventario:importar --sin-optimizar).
     */
    public function buscarSinOptimizar(string $codigo): ProductoEnvasado
    {
        $codigo = CodigoBarras::validar($codigo);

        try {
            $respuesta = $this->peticion()->get($this->url($codigo));
        } catch (ConnectionException $e) {
            throw $this->sinConexion($e);
        }

        return $this->interpretar($codigo, $respuesta);
    }

    /** Convierte la respuesta HTTP en un producto o en un error comprensible. */
    public function interpretar(string $codigo, Response $respuesta): ProductoEnvasado
    {
        if ($respuesta->status() === 404) {
            throw ProductoNoEncontrado::paraCodigo($codigo);
        }

        if ($respuesta->status() === 429) {
            throw new ServicioNoDisponible('Open Food Facts recibió demasiadas consultas seguidas. Espera un minuto e intenta de nuevo.', 429);
        }

        if ($respuesta->serverError()) {
            throw new ServicioNoDisponible("Open Food Facts no está disponible en este momento (error {$respuesta->status()}). Intenta de nuevo en unos minutos.");
        }

        if ($respuesta->failed()) {
            throw new ServicioNoDisponible("Open Food Facts rechazó la consulta (error {$respuesta->status()}).", 502);
        }

        $datos = $respuesta->json();

        if (! is_array($datos)) {
            throw new ServicioNoDisponible('Open Food Facts respondió con datos que no pudimos leer. Intenta de nuevo más tarde.', 502);
        }

        if ((int) ($datos['status'] ?? 0) !== 1 || ! is_array($datos['product'] ?? null)) {
            throw ProductoNoEncontrado::paraCodigo($codigo);
        }

        return ProductoEnvasado::desdeRespuesta($codigo, $datos['product']);
    }

    private function peticion(): PendingRequest
    {
        return $this->configurar(Http::createPendingRequest());
    }

    private function configurar(PendingRequest $peticion): PendingRequest
    {
        // Open Food Facts pide identificar la aplicación con un User-Agent propio
        return $peticion
            ->withUserAgent($this->userAgent)
            ->acceptJson()
            ->connectTimeout(min(5, $this->timeout))
            ->timeout($this->timeout);
    }

    private function url(string $codigo): string
    {
        return "{$this->baseUrl}/api/v2/product/{$codigo}.json";
    }

    /**
     * Error de red: se explica la causa concreta (certificados, DNS, tiempo de espera)
     * y el detalle técnico queda en storage/logs/laravel.log.
     */
    private function sinConexion(?Throwable $previa = null): ServicioNoDisponible
    {
        $detalle = $previa?->getMessage() ?? '';
        Log::warning('Open Food Facts: falla de conexión', ['detalle' => $detalle, 'url' => $this->baseUrl]);

        $mensaje = match (true) {
            (bool) preg_match('/cURL error (60|77)\b|SSL certificate|certificate verify|issuer certificate/i', $detalle) =>
                'No se pudo conectar con Open Food Facts de forma segura: PHP no tiene configurados los certificados SSL (cURL error 60). '
                .'Configura curl.cainfo y openssl.cafile en php.ini con el archivo cacert.pem (ver README) y reinicia el servidor.',
            (bool) preg_match('/cURL error 6\b|Could not resolve host/i', $detalle) =>
                'No se pudo conectar con Open Food Facts: no se encontró el servidor. Revisa la conexión a internet o el DNS.',
            (bool) preg_match('/cURL error 28\b|timed out/i', $detalle) =>
                'No se pudo conectar con Open Food Facts: no respondió a tiempo. Revisa la conexión a internet e intenta de nuevo.',
            (bool) preg_match('/cURL error (5|7|35|56)\b|proxy|Connection refused/i', $detalle) =>
                'No se pudo conectar con Open Food Facts: la conexión fue rechazada (¿firewall, proxy o VPN?). Revisa la red e intenta de nuevo.',
            default =>
                'No se pudo conectar con Open Food Facts. Revisa la conexión a internet e intenta de nuevo.',
        };

        return new ServicioNoDisponible($mensaje, 503, $previa);
    }

    /** @throws ProductoNoEncontrado si ya se sabe que no existe */
    private function desdeCache(string $codigo): ?ProductoEnvasado
    {
        $guardado = Cache::get($this->clave($codigo));

        if ($guardado === self::NO_ENCONTRADO) {
            throw ProductoNoEncontrado::paraCodigo($codigo);
        }

        return is_array($guardado) ? ProductoEnvasado::desdeArray($guardado) : null;
    }

    /** Guarda productos encontrados por un día y los "no encontrado" por 10 minutos. */
    private function guardarEnCache(string $codigo, callable $obtener): ProductoEnvasado
    {
        try {
            $producto = $obtener();
        } catch (ProductoNoEncontrado $e) {
            Cache::put($this->clave($codigo), self::NO_ENCONTRADO, now()->addMinutes(10));
            throw $e;
        }

        Cache::put($this->clave($codigo), $producto->toArray(), now()->addMinutes($this->cacheMinutos));

        return $producto;
    }

    private function clave(string $codigo): string
    {
        return "openfoodfacts:producto:{$codigo}";
    }
}
