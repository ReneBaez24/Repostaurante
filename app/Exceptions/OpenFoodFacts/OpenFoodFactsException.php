<?php

namespace App\Exceptions\OpenFoodFacts;

use RuntimeException;

/**
 * Error al consultar Open Food Facts. El mensaje siempre está escrito
 * para mostrarse tal cual a la persona que usa el sistema.
 */
abstract class OpenFoodFactsException extends RuntimeException
{
    /** Código HTTP con el que se responde en el endpoint JSON. */
    abstract public function status(): int;

    /** Identificador corto para el JSON (p. ej. "codigo_invalido"). */
    abstract public function tipo(): string;
}
