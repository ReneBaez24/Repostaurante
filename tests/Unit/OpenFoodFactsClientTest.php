<?php

namespace Tests\Unit;

use App\Exceptions\OpenFoodFacts\CodigoBarrasInvalido;
use App\Exceptions\OpenFoodFacts\OpenFoodFactsException;
use App\Exceptions\OpenFoodFacts\ProductoNoEncontrado;
use App\Exceptions\OpenFoodFacts\ServicioNoDisponible;
use App\Services\OpenFoodFacts\OpenFoodFactsClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenFoodFactsClientTest extends TestCase
{
    /**
     * Prueba 5: cada falla de la API (no existe, servicio caído, demasiadas consultas,
     * sin conexión) se convierte en un error con mensaje claro, y un código mal escrito
     * se rechaza sin llamar a la API.
     */
    public function test_convierte_fallas_de_la_api_en_mensajes_comprensibles(): void
    {
        Http::fake([
            '*/product/3017620422003*' => Http::response(['status' => 0, 'status_verbose' => 'product not found'], 404),
            '*/product/7501055363278*' => Http::response('Service Unavailable', 503),
            '*/product/737628064502*' => Http::response('Too Many Requests', 429),
            '*/product/7622210449283*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        $cliente = app(OpenFoodFactsClient::class);

        $esperados = [
            '3017620422003' => [ProductoNoEncontrado::class, 404, 'No encontramos el código 3017620422003'],
            '7501055363278' => [ServicioNoDisponible::class, 503, 'no está disponible en este momento'],
            '737628064502' => [ServicioNoDisponible::class, 429, 'demasiadas consultas'],
            '7622210449283' => [ServicioNoDisponible::class, 503, 'No se pudo conectar con Open Food Facts'],
            'ABC-123' => [CodigoBarrasInvalido::class, 422, 'solo puede contener números'],
        ];

        foreach ($esperados as $codigo => [$clase, $status, $mensaje]) {
            try {
                $cliente->buscar($codigo);
                $this->fail("Se esperaba un error para {$codigo}.");
            } catch (OpenFoodFactsException $e) {
                $this->assertInstanceOf($clase, $e, "Código {$codigo}");
                $this->assertSame($status, $e->status(), "Código {$codigo}");
                $this->assertStringContainsString($mensaje, $e->getMessage(), "Código {$codigo}");
            }
        }

        // El código mal escrito nunca llegó a la API
        Http::assertNotSent(fn ($peticion) => str_contains($peticion->url(), 'ABC'));
    }
}
