<?php

namespace App\Enums;

/**
 * El estado de una mesa no se guarda en la base de datos: se calcula
 * a partir del pedido que tiene en ese momento (ver Mesa::estado()).
 *
 * Libre → (abrir mesa) → Abierta → (enviar a cocina) → Esperando orden
 *       → (cocina termina) → Ocupada → (cobrar: sigue Ocupada) → (Liberar mesa) → Libre
 */
enum EstadoMesa: string
{
    case Libre = 'libre';
    case Abierta = 'abierta';
    case EsperandoOrden = 'esperando_orden';
    case Ocupada = 'ocupada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Libre => 'Libre',
            self::Abierta => 'Abierta',
            self::EsperandoOrden => 'Esperando orden',
            self::Ocupada => 'Ocupada',
        };
    }

    public function clase(): string
    {
        return match ($this) {
            self::Libre => 'badge-libre',
            self::Abierta => 'badge-abierta',
            self::EsperandoOrden => 'badge-espera',
            self::Ocupada => 'badge-ocupada',
        };
    }
}
