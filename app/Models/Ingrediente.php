<?php

namespace App\Models;

use App\Services\OpenFoodFacts\ProductoEnvasado;
use Illuminate\Database\Eloquent\Model;

class Ingrediente extends Model
{
    public const UNIDADES = ['pieza', 'paquete', 'caja', 'botella', 'lata', 'kg', 'g', 'l', 'ml'];

    protected $table = 'ingredientes';

    protected $fillable = [
        'codigo_barras', 'nombre', 'marca', 'presentacion', 'ingredientes', 'alergenos',
        'nutriscore', 'nutrimentos', 'imagen_url', 'existencias', 'unidad', 'origen',
    ];

    protected function casts(): array
    {
        return [
            'alergenos' => 'array',
            'nutrimentos' => 'array',
            'existencias' => 'decimal:2',
        ];
    }

    /** Columnas que se llenan con los datos de Open Food Facts. */
    public static function datosDesde(ProductoEnvasado $producto): array
    {
        return [
            'codigo_barras' => $producto->codigo,
            'nombre' => mb_substr($producto->nombre, 0, 150),
            'marca' => $producto->marca ? mb_substr($producto->marca, 0, 100) : null,
            'presentacion' => $producto->cantidad ? mb_substr($producto->cantidad, 0, 60) : null,
            'ingredientes' => $producto->ingredientes,
            'alergenos' => $producto->alergenos,
            'nutriscore' => $producto->nutriscore,
            'nutrimentos' => $producto->nutrimentos,
            'imagen_url' => $producto->imagenUrl && strlen($producto->imagenUrl) <= 500 ? $producto->imagenUrl : null,
            'origen' => 'openfoodfacts',
        ];
    }
}
