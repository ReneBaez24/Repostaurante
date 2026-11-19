<?php

namespace App\Models;

use App\Enums\EstadoItem;
use App\Enums\EstadoPedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = ['mesa_id', 'user_id', 'comensales', 'estado', 'abierto_at', 'cerrado_at', 'liberada_at'];

    protected function casts(): array
    {
        return [
            'comensales' => 'integer',
            'estado' => EstadoPedido::class,
            'abierto_at' => 'datetime',
            'cerrado_at' => 'datetime',
            'liberada_at' => 'datetime',
        ];
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mesero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class)->orderBy('id');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->where('estado', EstadoPedido::Abierto->value);
    }

    /** Pedidos que todavía ocupan su mesa: abiertos, o pagados sin liberar. */
    public function scopeEnMesa(Builder $query): Builder
    {
        return $query
            ->whereIn('estado', [EstadoPedido::Abierto->value, EstadoPedido::Pagado->value])
            ->whereNull('liberada_at');
    }

    public function estaPagado(): bool
    {
        return $this->estado === EstadoPedido::Pagado;
    }

    public function estaAbierto(): bool
    {
        return $this->estado === EstadoPedido::Abierto;
    }

    public function folio(): string
    {
        return '#'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /** Total de la cuenta (impuesto incluido). */
    public function total(): float
    {
        return round($this->items->sum(fn (PedidoItem $i) => $i->importe()), 2);
    }

    public function unidades(): int
    {
        return (int) $this->items->sum('cantidad');
    }

    public function unidadesSinEnviar(): int
    {
        return (int) $this->items->where('estado', EstadoItem::SinEnviar)->sum('cantidad');
    }

    public function todoPreparado(): bool
    {
        return $this->items->isNotEmpty()
            && $this->items->every(fn (PedidoItem $i) => $i->estado === EstadoItem::Preparado);
    }

    /** Cantidad total pedida de un producto (para el contador de la tarjeta). */
    public function cantidadDe(int $productoId): int
    {
        return (int) $this->items->where('producto_id', $productoId)->sum('cantidad');
    }

    public function minutosAbierto(): int
    {
        return (int) $this->abierto_at->diffInMinutes(now());
    }
}
