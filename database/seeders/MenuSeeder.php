<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = [
            'Ahumados' => [
                ['¼ pollo ahumado', 'Cuarto de pollo, humo de leña', 12.00],
                ['½ pollo ahumado', 'Medio pollo, ahumado artesanal', 22.00],
                ['Pollo entero ahumado', 'Pollo completo para compartir', 40.00],
            ],
            'Asados' => [
                ['¼ pollo asado', 'Cuarto de pollo, limón y especias', 9.50],
                ['½ pollo asado', 'Medio pollo, limón y especias', 18.00],
            ],
            'Bebidas' => [
                ['Limonada casera', 'Limón natural y hierbabuena', 3.50],
                ['Coca cola', 'Botella de 600 ml', 2.00],
                ['Agua fresca', 'Sabor del día', 2.50],
            ],
            'Guarniciones' => [
                ['Papas rústicas', 'Con romero y sal de la casa', 6.00],
                ['Ensalada de col', 'Col, zanahoria y aderezo cremoso', 4.00],
            ],
        ];

        $orden = 0;
        foreach ($menu as $nombreCategoria => $productos) {
            $categoria = Categoria::updateOrCreate(
                ['slug' => str($nombreCategoria)->slug()->toString()],
                ['nombre' => $nombreCategoria, 'orden' => $orden++],
            );

            foreach ($productos as [$nombre, $descripcion, $precio]) {
                Producto::updateOrCreate(
                    ['nombre' => $nombre],
                    [
                        'categoria_id' => $categoria->id,
                        'descripcion' => $descripcion,
                        'precio' => $precio,
                        'disponible' => true,
                    ],
                );
            }
        }
    }
}
