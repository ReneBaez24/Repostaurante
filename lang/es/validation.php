<?php

// Mensajes de validación en español (las reglas que no estén aquí usan el inglés de Laravel)
return [
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':Attribute no es una fecha válida.',
    'email' => ':Attribute debe ser un correo electrónico válido.',
    'enum' => 'El valor elegido en :attribute no es válido.',
    'exists' => 'El valor elegido en :attribute no existe.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'password' => [
        'min' => 'La contraseña debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'role' => 'rol',
        'numero' => 'número de mesa',
        'capacidad' => 'capacidad',
        'zona' => 'zona',
        'comensales' => 'comensales',
        'categoria_id' => 'categoría',
        'precio' => 'precio',
        'descripcion' => 'descripción',
        'metodo' => 'método de pago',
        'propina' => 'propina',
        'codigo' => 'código de barras',
        'existencias' => 'existencias',
        'unidad' => 'unidad',
        'presentacion' => 'presentación',
        'marca' => 'marca',
        'notas' => 'notas',
        'fecha' => 'fecha',
    ],
];
