<?php

namespace App\Exceptions\OpenFoodFacts;

class CodigoBarrasInvalido extends OpenFoodFactsException
{
    public function status(): int
    {
        return 422;
    }

    public function tipo(): string
    {
        return 'codigo_invalido';
    }
}
