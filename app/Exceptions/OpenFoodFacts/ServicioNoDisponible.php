<?php

namespace App\Exceptions\OpenFoodFacts;

use Throwable;

class ServicioNoDisponible extends OpenFoodFactsException
{
    public function __construct(string $mensaje, private int $estado = 503, ?Throwable $previa = null)
    {
        parent::__construct($mensaje, 0, $previa);
    }

    public function status(): int
    {
        return $this->estado;
    }

    public function tipo(): string
    {
        return $this->estado === 429 ? 'demasiadas_consultas' : 'servicio_no_disponible';
    }
}
