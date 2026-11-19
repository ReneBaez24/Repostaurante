<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User, 'roles' => Rol::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::enum(Rol::class)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create($datos); // el modelo cifra la contraseña

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario, 'roles' => Rol::cases()]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role' => ['required', Rule::enum(Rol::class)],
            'password' => ['nullable', 'confirmed', Password::min(8)], // vacío = no cambiar
        ]);

        if ($usuario->esAdmin() && $datos['role'] !== Rol::Admin->value && $this->esUltimoAdmin($usuario)) {
            return back()->withInput()->with('error', 'Debe existir al menos un administrador.');
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }
        if ($usuario->esAdmin() && $this->esUltimoAdmin($usuario)) {
            return back()->with('error', 'Debe existir al menos un administrador.');
        }
        if ($usuario->pedidos()->exists() || $usuario->pagos()->exists()) {
            return back()->with('error', 'Este usuario tiene pedidos o cobros registrados; no se puede eliminar para conservar el historial.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario eliminado.');
    }

    private function esUltimoAdmin(User $usuario): bool
    {
        return User::where('role', Rol::Admin->value)->whereKeyNot($usuario->id)->doesntExist();
    }
}
