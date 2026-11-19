<?php

namespace App\Enums;

enum ZonaMesa: string
{
    case Interior = 'interior';
    case Terraza = 'terraza';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Interior => 'Salón interior',
            self::Terraza => 'Terraza',
        };
    }
}
