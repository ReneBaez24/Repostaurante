<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Usuarios iniciales. Las contraseñas se guardan cifradas (bcrypt)
     * gracias al cast 'hashed' del modelo User.
     * ¡Cámbialas desde el panel de administración en producción!
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@punkbrothers.com'],
            ['name' => 'Administrador', 'password' => 'admin123', 'role' => Rol::Admin],
        );

        User::updateOrCreate(
            ['email' => 'rene@punkbrothers.com'],
            ['name' => 'Rene Baez', 'password' => 'mesero123', 'role' => Rol::Usuario],
        );
    }
}
