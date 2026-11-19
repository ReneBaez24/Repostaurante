@extends('layouts.app')

@section('title', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
<div style="display: flex; justify-content: center; padding: 30px;">
    <form class="sidebar" style="width: 450px; flex: none;" method="POST"
          action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <h3>{{ $usuario->exists ? 'EDITAR USUARIO' : 'NUEVO USUARIO' }}</h3>

        <label class="campo">Nombre</label>
        <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required maxlength="100">

        <label class="campo">Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required>

        <label class="campo">Rol</label>
        <select name="role">
            @foreach ($roles as $rol)
                <option value="{{ $rol->value }}" @selected(old('role', $usuario->role?->value ?? 'usuario') === $rol->value)>{{ $rol->etiqueta() }}</option>
            @endforeach
        </select>
        <p class="muted" style="margin: -8px 0 15px;">Administrador: acceso total. Usuario: mesas, pedidos, cocina y cobro.</p>

        <label class="campo">Contraseña {{ $usuario->exists ? '(déjala vacía para no cambiarla)' : '' }}</label>
        <input type="password" name="password" minlength="8" @required(! $usuario->exists) autocomplete="new-password">

        <label class="campo">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" minlength="8" @required(! $usuario->exists) autocomplete="new-password">

        <button class="btn">Guardar</button>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline btn-block mt-10">Cancelar</a>
    </form>
</div>
@endsection
