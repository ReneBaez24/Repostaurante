<?php

namespace Tests\Unit;

use App\Support\CalculadoraCuenta;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculadoraCuentaTest extends TestCase
{
    /**
     * Prueba 3: la cuenta de la pantalla de cobro ($46.50) da el impuesto incluido,
     * la propina y el total correctos, redondeados a centavos.
     */
    public function test_calcula_impuesto_incluido_propina_y_total(): void
    {
        $this->assertSame(4.23, CalculadoraCuenta::impuestoIncluido(46.50, 0.10));
        $this->assertSame(4.65, CalculadoraCuenta::propina(46.50, 10));
        $this->assertSame(51.15, CalculadoraCuenta::total(46.50, 10));

        $this->assertSame(0.0, CalculadoraCuenta::propina(46.50, 0));
        $this->assertSame(0.5, CalculadoraCuenta::propina(10.05, 5));   // 0.5025 → 0.50
        $this->assertSame(0.0, CalculadoraCuenta::impuestoIncluido(0.0, 0.10));

        $this->expectException(InvalidArgumentException::class);
        CalculadoraCuenta::propina(46.50, -5);
    }
}
