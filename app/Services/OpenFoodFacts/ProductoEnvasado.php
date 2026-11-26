<?php

namespace App\Services\OpenFoodFacts;

use Illuminate\Support\Str;

/**
 * Datos de un ingrediente envasado, ya traducidos del formato de Open Food Facts.
 */
final class ProductoEnvasado
{
    /** Alérgenos de Open Food Facts (etiquetas "en:…") en español. */
    private const ALERGENOS = [
        'gluten' => 'Gluten',
        'milk' => 'Leche',
        'eggs' => 'Huevo',
        'nuts' => 'Frutos de cáscara',
        'peanuts' => 'Cacahuate',
        'soybeans' => 'Soya',
        'fish' => 'Pescado',
        'crustaceans' => 'Crustáceos',
        'molluscs' => 'Moluscos',
        'celery' => 'Apio',
        'mustard' => 'Mostaza',
        'sesame-seeds' => 'Sésamo',
        'sulphur-dioxide-and-sulphites' => 'Sulfitos',
        'lupin' => 'Altramuz',
    ];

    /** Nutrimentos por cada 100 g que se guardan: clave OFF => clave propia. */
    private const NUTRIMENTOS = [
        'energy-kcal_100g' => 'energia_kcal',
        'fat_100g' => 'grasas',
        'sugars_100g' => 'azucares',
        'salt_100g' => 'sal',
    ];

    /** Campos que se piden a la API (el resto del producto no se descarga). */
    public const CAMPOS = [
        'code', 'product_name', 'product_name_es', 'generic_name', 'generic_name_es', 'brands', 'quantity',
        'ingredients_text', 'ingredients_text_es', 'allergens_tags', 'nutriscore_grade',
        'image_front_url', 'image_url', 'nutriments',
    ];

    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly ?string $marca,
        public readonly ?string $cantidad,
        public readonly ?string $ingredientes,
        public readonly array $alergenos,
        public readonly ?string $nutriscore,
        public readonly ?string $imagenUrl,
        public readonly array $nutrimentos,
    ) {}

    /** @param array<string, mixed> $producto el objeto "product" de la respuesta de la API */
    public static function desdeRespuesta(string $codigo, array $producto): self
    {
        $texto = fn (string ...$claves) => self::primerTexto($producto, $claves);

        $marca = $texto('brands');
        $nutriscore = strtolower((string) ($producto['nutriscore_grade'] ?? ''));

        $nutrimentos = [];
        foreach (self::NUTRIMENTOS as $origen => $destino) {
            $valor = $producto['nutriments'][$origen] ?? null;
            if (is_numeric($valor)) {
                $nutrimentos[$destino] = (float) $valor;
            }
        }

        return new self(
            codigo: $codigo,
            nombre: $texto('product_name_es', 'product_name', 'generic_name_es', 'generic_name') ?? 'Producto sin nombre',
            marca: $marca ? trim(explode(',', $marca)[0]) : null,
            cantidad: $texto('quantity'),
            ingredientes: $texto('ingredients_text_es', 'ingredients_text'),
            alergenos: self::traducirAlergenos($producto['allergens_tags'] ?? []),
            nutriscore: in_array($nutriscore, ['a', 'b', 'c', 'd', 'e'], true) ? strtoupper($nutriscore) : null,
            imagenUrl: $texto('image_front_url', 'image_url'),
            nutrimentos: $nutrimentos,
        );
    }

    /** Reconstruye el objeto desde la caché. */
    public static function desdeArray(array $datos): self
    {
        return new self(...$datos);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    private static function primerTexto(array $producto, array $claves): ?string
    {
        foreach ($claves as $clave) {
            $valor = $producto[$clave] ?? null;
            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return null;
    }

    private static function traducirAlergenos(mixed $etiquetas): array
    {
        if (! is_array($etiquetas)) {
            return [];
        }

        return array_values(array_unique(array_map(function ($etiqueta) {
            $clave = Str::after((string) $etiqueta, ':');

            return self::ALERGENOS[$clave] ?? Str::ucfirst(str_replace('-', ' ', $clave));
        }, $etiquetas)));
    }
}
