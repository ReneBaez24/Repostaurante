<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Tarjeta = 'tarjeta';
    case Efectivo = 'efectivo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tarjeta => 'Tarjeta',
            self::Efectivo => 'Efectivo',
        };
    }
}
