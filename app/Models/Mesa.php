<?php

namespace App\Models;

use App\Enums\EstadoItem;
use App\Enums\EstadoMesa;
use App\Enums\ZonaMesa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = ['numero', 'capacidad', 'zona', 'activa'];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'capacidad' => 'integer',
            'zona' => ZonaMesa::class,
            'activa' => 'boolean',
        ];
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * El pedido que ocupa la mesa ahora: abierto, o ya pagado pero sin liberar.
     * Como máximo hay uno por mesa.
     */
    public function pedidoActual(): HasOne
    {
        return $this->hasOne(Pedido::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $q) => $q->enMesa(),
        );
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Libre: sin pedido en la mesa.
     * Abierta: mesa abierta, todavía no se envía nada a cocina.
     * Esperando orden: hay productos en preparación en cocina.
     * Ocupada: cocina ya entregó todo lo enviado. Sigue ocupada después
     *          de cobrar, hasta que alguien pulsa "Liberar mesa".
     */
    public function estado(): EstadoMesa
    {
        $pedido = $this->pedidoActual;

        if (! $pedido) {
            return EstadoMesa::Libre;
        }

        if ($pedido->items->contains('estado', EstadoItem::EnPreparacion)) {
            return EstadoMesa::EsperandoOrden;
        }

        if ($pedido->estaPagado() || $pedido->items->contains('estado', EstadoItem::Preparado)) {
            return EstadoMesa::Ocupada;
        }

        return EstadoMesa::Abierta;
    }

    public function numeroFormateado(): string
    {
        return str_pad((string) $this->numero, 2, '0', STR_PAD_LEFT);
    }
}
