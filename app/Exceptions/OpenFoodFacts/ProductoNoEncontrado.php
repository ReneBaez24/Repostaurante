<?php

namespace App\Exceptions\OpenFoodFacts;

class ProductoNoEncontrado extends OpenFoodFactsException
{
    public static function paraCodigo(string $codigo): self
    {
        return new self("No encontramos el código {$codigo} en Open Food Facts. Revisa el número o registra el ingrediente manualmente.");
    }

    public function status(): int
    {
        return 404;
    }

    public function tipo(): string
    {
        return 'no_encontrado';
    }
}
