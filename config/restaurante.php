<?php

return [
    'nombre' => 'PunkBrothers',
    'lema' => 'Los mejores pollos al vapor',

    // Impuesto incluido en los precios del menú (10 % como en la pantalla de cobro)
    'impuesto' => (float) env('RESTAURANTE_IMPUESTO', 0.10),

    // Opciones de propina que se muestran en el cobro (porcentajes)
    'propinas' => [0, 5, 10],
];
