<?php

namespace App\Enums;

/**
 * Ciclo de vida de cada renglón del pedido:
 * sin_enviar → (Enviar a cocina) → en_preparacion → (Cocina) → preparado
 */
enum EstadoItem: string
{
    case SinEnviar = 'sin_enviar';
    case EnPreparacion = 'en_preparacion';
    case Preparado = 'preparado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SinEnviar => 'Sin enviar',
            self::EnPreparacion => 'En preparación',
            self::Preparado => 'Preparado',
        };
    }

    public function clase(): string
    {
        return match ($this) {
            self::SinEnviar => 'badge-sin-enviar',
            self::EnPreparacion => 'badge-espera',
            self::Preparado => 'badge-libre',
        };
    }
}
