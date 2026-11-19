<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'usuario',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // la contraseña se guarda cifrada con bcrypt automáticamente
            'role' => Rol::class,
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === Rol::Admin;
    }

    public function tieneRol(Rol|string ...$roles): bool
    {
        foreach ($roles as $rol) {
            $rol = $rol instanceof Rol ? $rol : Rol::from($rol);
            if ($this->role === $rol) {
                return true;
            }
        }

        return false;
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
