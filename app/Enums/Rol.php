<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Usuario = 'usuario';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Usuario => 'Usuario',
        };
    }
}
