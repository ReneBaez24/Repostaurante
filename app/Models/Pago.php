<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id', 'user_id', 'metodo', 'consumo', 'impuesto',
        'propina_porcentaje', 'propina', 'total',
    ];

    protected function casts(): array
    {
        return [
            'metodo' => MetodoPago::class,
            'consumo' => 'decimal:2',
            'impuesto' => 'decimal:2',
            'propina_porcentaje' => 'integer',
            'propina' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
