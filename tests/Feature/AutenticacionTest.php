<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_enviado_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_la_contrasena_se_guarda_cifrada(): void
    {
        $usuario = User::factory()->create(['password' => 'secreto123']);

        $this->assertNotSame('secreto123', $usuario->getRawOriginal('password'));
        $this->assertTrue(Hash::check('secreto123', $usuario->getRawOriginal('password')));
    }

    public function test_login_correcto_y_redireccion_por_rol(): void
    {
        User::factory()->create(['email' => 'mesero@test.com', 'password' => 'secreto123']);
        User::factory()->admin()->create(['email' => 'admin@test.com', 'password' => 'secreto123']);

        $this->post('/login', ['email' => 'mesero@test.com', 'password' => 'secreto123'])
            ->assertRedirect(route('overview'));
        $this->post('/logout');

        $this->post('/login', ['email' => 'admin@test.com', 'password' => 'secreto123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_incorrecto(): void
    {
        User::factory()->create(['email' => 'mesero@test.com', 'password' => 'secreto123']);

        $this->post('/login', ['email' => 'mesero@test.com', 'password' => 'mala'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_usuario_no_puede_entrar_al_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }

    public function test_admin_crea_usuario_con_rol(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'Nuevo',
            'email' => 'nuevo@test.com',
            'role' => 'admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.usuarios.index'));

        $nuevo = User::where('email', 'nuevo@test.com')->first();
        $this->assertSame(Rol::Admin, $nuevo->role);
        $this->assertTrue(Hash::check('password123', $nuevo->password));
    }

    public function test_no_se_puede_quitar_el_ultimo_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'usuario',
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->esAdmin());
    }
}
