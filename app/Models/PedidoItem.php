<?php

namespace App\Models;

use App\Enums\EstadoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_items';

    protected $fillable = [
        'pedido_id', 'producto_id', 'cantidad', 'precio_unitario',
        'notas', 'estado', 'enviado_at', 'preparado_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'estado' => EstadoItem::class,
            'enviado_at' => 'datetime',
            'preparado_at' => 'datetime',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function importe(): float
    {
        return round($this->cantidad * (float) $this->precio_unitario, 2);
    }
}
