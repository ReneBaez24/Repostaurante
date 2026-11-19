@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="container" style="display: block;">
    <div class="section-head">
        <h2 style="margin: 0;">USUARIOS</h2>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-sm">+ Nuevo usuario</a>
    </div>

    <div class="sidebar">
        <table class="tabla">
            <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Alta</th><th></th></tr>
            @foreach ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->name }} @if ($usuario->is(auth()->user())) <span class="muted">(tú)</span> @endif</td>
                    <td>{{ $usuario->email }}</td>
                    <td><span class="badge {{ $usuario->esAdmin() ? 'badge-admin' : 'badge-ocupada' }}">{{ $usuario->role->etiqueta() }}</span></td>
                    <td>{{ $usuario->created_at?->format('d/m/Y') }}</td>
                    <td>
                        <div class="acciones">
                            <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-outline btn-sm">Editar</a>
                            @unless ($usuario->is(auth()->user()))
                                <form action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar a {{ $usuario->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <p class="mt-10"><a href="{{ route('admin.dashboard') }}">← Volver al panel</a></p>
</div>
@endsection
