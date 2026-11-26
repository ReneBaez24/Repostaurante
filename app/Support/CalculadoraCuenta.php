<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Reglas de dinero de una cuenta: impuesto incluido, propina y total.
 * Todo se redondea a centavos.
 */
final class CalculadoraCuenta
{
    /** Los precios ya incluyen impuesto: impuesto = total − total / (1 + tasa). */
    public static function impuestoIncluido(float $total, float $tasa): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round($total - $total / (1 + $tasa), 2);
    }

    public static function propina(float $consumo, int $porcentaje): float
    {
        if ($porcentaje < 0) {
            throw new InvalidArgumentException('La propina no puede ser negativa.');
        }

        return round($consumo * $porcentaje / 100, 2);
    }

    public static function total(float $consumo, int $porcentajePropina): float
    {
        return round($consumo + self::propina($consumo, $porcentajePropina), 2);
    }
}
