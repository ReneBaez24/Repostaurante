<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case Abierto = 'abierto';
    case Pagado = 'pagado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Abierto => 'En servicio',
            self::Pagado => 'Pagado',
            self::Cancelado => 'Cancelado',
        };
    }
}
