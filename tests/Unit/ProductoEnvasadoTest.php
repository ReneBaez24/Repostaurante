<?php

namespace Tests\Unit;

use App\Services\OpenFoodFacts\ProductoEnvasado;
use PHPUnit\Framework\TestCase;

class ProductoEnvasadoTest extends TestCase
{
    /**
     * Prueba 4: la respuesta de Open Food Facts se traduce a nuestro formato:
     * nombre en español, primera marca, alérgenos en español, Nutri-Score en mayúscula
     * y valores por defecto cuando faltan datos.
     */
    public function test_traduce_la_respuesta_de_la_api_y_tolera_datos_faltantes(): void
    {
        $producto = ProductoEnvasado::desdeRespuesta('3017620422003', [
            'product_name' => 'Nutella',
            'product_name_es' => 'Nutella crema de avellanas',
            'brands' => 'Ferrero, Nutella',
            'quantity' => '400 g',
            'ingredients_text_es' => 'Azúcar, aceite de palma, avellanas 13%, leche desnatada en polvo 8,7%',
            'allergens_tags' => ['en:milk', 'en:nuts', 'en:soybeans', 'en:some-new-allergen'],
            'nutriscore_grade' => 'e',
            'image_front_url' => 'https://images.openfoodfacts.org/nutella.jpg',
            'nutriments' => ['energy-kcal_100g' => 539, 'fat_100g' => 30.9, 'sugars_100g' => 56.3, 'salt_100g' => 0.107],
        ]);

        $this->assertSame('Nutella crema de avellanas', $producto->nombre);
        $this->assertSame('Ferrero', $producto->marca);
        $this->assertSame('400 g', $producto->cantidad);
        $this->assertStringStartsWith('Azúcar', $producto->ingredientes);
        $this->assertSame(['Leche', 'Frutos de cáscara', 'Soya', 'Some new allergen'], $producto->alergenos);
        $this->assertSame('E', $producto->nutriscore);
        $this->assertSame(['energia_kcal' => 539.0, 'grasas' => 30.9, 'azucares' => 56.3, 'sal' => 0.107], $producto->nutrimentos);

        $vacio = ProductoEnvasado::desdeRespuesta('96385074', ['nutriscore_grade' => 'unknown', 'brands' => '  ']);

        $this->assertSame('Producto sin nombre', $vacio->nombre);
        $this->assertNull($vacio->marca);
        $this->assertNull($vacio->ingredientes);
        $this->assertSame([], $vacio->alergenos);
        $this->assertNull($vacio->nutriscore);
        $this->assertSame([], $vacio->nutrimentos);
    }
}
