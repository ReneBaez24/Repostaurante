<?php

/**
 * Imitación local de la API de Open Food Facts, sólo para medir rendimiento
 * de forma repetible (sin depender de internet ni de los límites de la API).
 *
 *   PHP_CLI_SERVER_WORKERS=32 LATENCIA_MS=250 php -S 127.0.0.1:8099 tools/rendimiento/servidor-simulado.php
 *
 * - Responde GET /api/v2/product/{codigo}.json y /api/v2/search como la API v2.
 * - Espera LATENCIA_MS milisegundos por petición (tiempo de red + servidor).
 * - Sin ?fields= devuelve un producto "completo" grande (como la API real, que
 *   incluye cientos de campos); con ?fields= devuelve sólo los campos pedidos.
 * - Los códigos cuyo penúltimo dígito es 0 no existen (≈10 %): 404.
 */
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Búsqueda (la usa rendimiento:medir --url para obtener códigos que sí existen)
if ($ruta === '/api/v2/search') {
    header('Content-Type: application/json');
    $productos = [['code' => '123'], ['code' => 'abc-interno']]; // códigos no GTIN, como en la base real
    for ($i = 1; count($productos) < (int) ($_GET['page_size'] ?? 24); $i++) {
        $base = '750'.str_pad((string) ($i * 7919 % 1000000000), 9, '1', STR_PAD_LEFT);
        $suma = 0;
        foreach (str_split(strrev($base)) as $j => $d) {
            $suma += (int) $d * ($j % 2 === 0 ? 3 : 1);
        }
        $codigo = $base.((10 - $suma % 10) % 10);
        if ($codigo[11] !== '0') { // que exista en este servidor
            $productos[] = ['code' => $codigo];
        }
    }
    echo json_encode(['count' => count($productos), 'page' => 1, 'products' => $productos]);

    return true;
}

if (! preg_match('#^/api/v2/product/(\d{8,14})\.json$#', $ruta, $m)) {
    http_response_code(404);
    echo 'Ruta no encontrada';

    return true;
}

usleep((int) (getenv('LATENCIA_MS') ?: 250) * 1000);
header('Content-Type: application/json');

$codigo = $m[1];
if ($codigo[strlen($codigo) - 2] === '0') {
    http_response_code(404);
    echo json_encode(['code' => $codigo, 'status' => 0, 'status_verbose' => 'product not found']);

    return true;
}

$producto = productoCompleto($codigo);

if (! empty($_GET['fields'])) {
    $producto = array_intersect_key($producto, array_flip(explode(',', $_GET['fields'])));
}

echo json_encode(['code' => $codigo, 'product' => $producto, 'status' => 1, 'status_verbose' => 'product found']);

return true;

/** Producto con la estructura de la API real y un tamaño similar (decenas de KB). */
function productoCompleto(string $codigo): array
{
    mt_srand((int) substr($codigo, -6));
    $idiomas = ['es', 'en', 'fr', 'de', 'it', 'pt', 'nl', 'pl', 'ro', 'sv'];
    $nombre = 'Producto de prueba '.substr($codigo, -4);

    $ingredientes = [];
    foreach (range(1, 30) as $i) {
        $ingredientes[] = [
            'id' => "en:ingrediente-{$i}", 'text' => "Ingrediente {$i}", 'rank' => $i,
            'percent_estimate' => mt_rand(1, 3000) / 100, 'percent_min' => 0, 'percent_max' => 100,
            'vegan' => 'maybe', 'vegetarian' => 'yes', 'from_palm_oil' => 'no', 'ciqual_food_code' => (string) mt_rand(10000, 99999),
            'ecobalyse_code' => md5("{$codigo}{$i}"),
        ];
    }

    $nutrimentos = [];
    foreach (['energy', 'energy-kcal', 'energy-kj', 'fat', 'saturated-fat', 'carbohydrates', 'sugars', 'fiber', 'proteins', 'salt', 'sodium',
        'calcium', 'iron', 'vitamin-a', 'vitamin-c', 'vitamin-d', 'potassium', 'magnesium', 'zinc', 'cholesterol', 'trans-fat', 'polyols',
        'starch', 'lactose', 'caffeine', 'nova-group', 'fruits-vegetables-nuts-estimate-from-ingredients'] as $n) {
        $valor = mt_rand(0, 60000) / 100;
        foreach (['', '_100g', '_serving', '_value', '_unit', '_label', '_prepared', '_prepared_100g', '_modifier'] as $sufijo) {
            $nutrimentos["{$n}{$sufijo}"] = $sufijo === '_unit' ? 'g' : ($sufijo === '_label' ? ucfirst($n) : $valor);
        }
    }

    $imagenes = [];
    foreach (['front', 'ingredients', 'nutrition', 'packaging'] as $tipo) {
        foreach ($idiomas as $l) {
            $imagenes["{$tipo}_{$l}"] = ['imgid' => (string) mt_rand(1, 40), 'rev' => (string) mt_rand(1, 90), 'angle' => 0, 'geometry' => '0x0-0-0',
                'sizes' => ['100' => ['w' => 75, 'h' => 100], '200' => ['w' => 150, 'h' => 200], '400' => ['w' => 300, 'h' => 400], 'full' => ['w' => 1200, 'h' => 1600]]];
        }
    }

    $producto = [
        'code' => $codigo,
        'product_name' => $nombre, 'product_name_es' => $nombre, 'generic_name' => 'Ingrediente envasado',
        'brands' => 'Marca '.mt_rand(1, 40), 'quantity' => mt_rand(1, 20) * 50 .' g',
        'ingredients_text' => implode(', ', array_column($ingredientes, 'text')),
        'ingredients' => $ingredientes,
        'ingredients_hierarchy' => array_column($ingredientes, 'id'),
        'ingredients_tags' => array_column($ingredientes, 'id'),
        'ingredients_original_tags' => array_column($ingredientes, 'id'),
        'allergens_tags' => array_slice(['en:milk', 'en:gluten', 'en:nuts', 'en:soybeans', 'en:eggs'], 0, mt_rand(0, 3)),
        'nutriscore_grade' => ['a', 'b', 'c', 'd', 'e'][mt_rand(0, 4)],
        'nutriments' => $nutrimentos,
        'images' => $imagenes,
        'image_front_url' => "https://images.openfoodfacts.org/images/products/{$codigo}/front_es.4.400.jpg",
        'image_url' => "https://images.openfoodfacts.org/images/products/{$codigo}/front_es.4.400.jpg",
        'categories_hierarchy' => array_map(fn ($i) => "en:categoria-{$i}", range(1, 15)),
        'countries_tags' => array_map(fn ($l) => "en:pais-{$l}", $idiomas),
        'data_quality_tags' => array_map(fn ($i) => "en:control-de-calidad-{$i}", range(1, 40)),
        'states_hierarchy' => array_map(fn ($i) => "en:estado-{$i}", range(1, 25)),
        '_keywords' => array_map(fn ($i) => "palabra{$i}", range(1, 30)),
    ];

    foreach ($idiomas as $l) {
        $producto["product_name_{$l}"] = "{$nombre} ({$l})";
        $producto["ingredients_text_{$l}"] = $producto['ingredients_text'];
        $producto["packaging_text_{$l}"] = 'Bolsa de plástico, caja de cartón, reciclable según la normativa local.';
    }

    return $producto;
}
