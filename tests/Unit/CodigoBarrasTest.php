<?php

namespace Tests\Unit;

use App\Exceptions\OpenFoodFacts\CodigoBarrasInvalido;
use App\Support\CodigoBarras;
use PHPUnit\Framework\TestCase;

class CodigoBarrasTest extends TestCase
{
    /**
     * Prueba 1: los códigos válidos de los 4 formatos se aceptan, y los espacios
     * o guiones que se cuelan al escribir o escanear se limpian.
     */
    public function test_acepta_codigos_validos_y_los_normaliza(): void
    {
        $this->assertSame('3017620422003', CodigoBarras::normalizar(' 3017-6204 22003 '));

        $this->assertSame('96385074', CodigoBarras::validar('96385074'));             // EAN-8
        $this->assertSame('737628064502', CodigoBarras::validar('737628064502'));     // UPC-A
        $this->assertSame('3017620422003', CodigoBarras::validar('3017 6204 22003')); // EAN-13
        $this->assertSame('00012345600012', CodigoBarras::validar('00012345600012')); // GTIN-14

        $this->assertTrue(CodigoBarras::digitoVerificadorValido('7501055363278'));
        $this->assertFalse(CodigoBarras::digitoVerificadorValido('7501055363279'));
    }

    /**
     * Prueba 2: cada error de captura se rechaza con un mensaje que la persona entiende,
     * antes de gastar una consulta a la API.
     */
    public function test_rechaza_codigos_invalidos_con_mensajes_comprensibles(): void
    {
        $casos = [
            '' => 'Escribe un código de barras',
            '30176ABC22003' => 'solo puede contener números',
            '12345' => 'tiene 5 dígitos',
            '3017620422004' => 'El último dígito no coincide',
        ];

        foreach ($casos as $entrada => $mensajeEsperado) {
            try {
                CodigoBarras::validar($entrada);
                $this->fail("Se esperaba que «{$entrada}» fuera rechazado.");
            } catch (CodigoBarrasInvalido $e) {
                $this->assertStringContainsString($mensajeEsperado, $e->getMessage());
                $this->assertSame(422, $e->status());
            }
        }
    }
}
