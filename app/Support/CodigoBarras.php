<?php

namespace App\Support;

use App\Exceptions\OpenFoodFacts\CodigoBarrasInvalido;

/**
 * Reglas de los códigos de barras de productos (GTIN: EAN-8, UPC-A, EAN-13, GTIN-14).
 */
final class CodigoBarras
{
    /** Longitudes válidas: EAN-8, UPC-A (12), EAN-13 y GTIN-14. */
    public const LONGITUDES = [8, 12, 13, 14];

    /** Quita espacios, guiones y puntos que se cuelan al escribir o escanear. */
    public static function normalizar(string $entrada): string
    {
        return preg_replace('/[\s\-.]+/u', '', trim($entrada)) ?? '';
    }

    /**
     * Dígito verificador GTIN (módulo 10): de derecha a izquierda, sin contar el último,
     * los dígitos se multiplican alternadamente por 3 y por 1.
     */
    public static function digitoVerificadorValido(string $digitos): bool
    {
        if ($digitos === '' || ! ctype_digit($digitos)) {
            return false;
        }

        $cuerpo = strrev(substr($digitos, 0, -1));
        $suma = 0;
        for ($i = 0, $n = strlen($cuerpo); $i < $n; $i++) {
            $suma += (int) $cuerpo[$i] * ($i % 2 === 0 ? 3 : 1);
        }

        return (10 - $suma % 10) % 10 === (int) substr($digitos, -1);
    }

    /**
     * Devuelve el código limpio o lanza CodigoBarrasInvalido con un mensaje para la persona usuaria.
     */
    public static function validar(string $entrada): string
    {
        $codigo = self::normalizar($entrada);

        if ($codigo === '') {
            throw new CodigoBarrasInvalido('Escribe un código de barras.');
        }

        if (! ctype_digit($codigo)) {
            throw new CodigoBarrasInvalido("El código de barras solo puede contener números (recibido: «{$codigo}»).");
        }

        $largo = strlen($codigo);
        if (! in_array($largo, self::LONGITUDES, true)) {
            throw new CodigoBarrasInvalido("El código debe tener 8, 12, 13 o 14 dígitos; el que escribiste tiene {$largo} dígitos.");
        }

        if (! self::digitoVerificadorValido($codigo)) {
            throw new CodigoBarrasInvalido('El último dígito no coincide con el resto del código. Revisa que lo hayas copiado completo y sin errores.');
        }

        return $codigo;
    }
}
