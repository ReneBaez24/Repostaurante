<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Prueba la conexión con Open Food Facts y muestra el error técnico exacto.
 *
 *   php artisan openfoodfacts:diagnosticar
 */
class DiagnosticarOpenFoodFacts extends Command
{
    protected $signature = 'openfoodfacts:diagnosticar {codigo=3017620422003 : Código de barras de prueba (Nutella)}';

    protected $description = 'Prueba la conexión con Open Food Facts y muestra la causa exacta si falla';

    public function handle(): int
    {
        $config = config('services.openfoodfacts');
        $url = rtrim($config['base_url'], '/')."/api/v2/product/{$this->argument('codigo')}.json?fields=code,product_name";

        $this->table(['Dato', 'Valor'], [
            ['PHP', PHP_VERSION.' ('.php_ini_loaded_file().')'],
            ['Extensión curl', extension_loaded('curl') ? 'sí ('.(curl_version()['ssl_version'] ?? '?').')' : 'NO: actívala en php.ini (extension=curl)'],
            ['curl.cainfo', ini_get('curl.cainfo') ?: '(vacío)'],
            ['openssl.cafile', ini_get('openssl.cafile') ?: '(vacío)'],
            ['URL', $url],
        ]);

        try {
            $inicio = microtime(true);
            $respuesta = Http::withUserAgent($config['user_agent'])->timeout($config['timeout'])->get($url);
            $ms = round((microtime(true) - $inicio) * 1000);
        } catch (Throwable $e) {
            $this->error('Falló la conexión:');
            $this->line($e->getMessage());
            $this->newLine();

            if (preg_match('/cURL error (60|77)\b|SSL certificate|issuer certificate/i', $e->getMessage())) {
                $this->warn('Causa: PHP no tiene los certificados SSL. Solución:');
                $this->line('  1. Descarga https://curl.se/ca/cacert.pem (por ejemplo a C:\\php\\extras\\ssl\\cacert.pem)');
                $this->line('  2. En '.(php_ini_loaded_file() ?: 'php.ini').' agrega o descomenta:');
                $this->line('       curl.cainfo = "C:\\php\\extras\\ssl\\cacert.pem"');
                $this->line('       openssl.cafile = "C:\\php\\extras\\ssl\\cacert.pem"');
                $this->line('  3. Cierra y vuelve a abrir "php artisan serve" y corre este comando otra vez.');
            }

            return self::FAILURE;
        }

        $this->info("Respuesta HTTP {$respuesta->status()} en {$ms} ms.");
        $this->line('Producto: '.($respuesta->json('product.product_name') ?? '(sin nombre o no encontrado)'));

        return $respuesta->successful() ? self::SUCCESS : self::FAILURE;
    }
}
