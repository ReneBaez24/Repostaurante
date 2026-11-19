@extends('layouts.app')

@section('title', 'Menú')

@section('content')
<div class="container" style="display: block;">
    <div class="section-head">
        <h2 style="margin: 0;">MENÚ</h2>
        <a href="{{ route('admin.productos.create') }}" class="btn btn-sm">+ Nuevo producto</a>
    </div>

    <div class="sidebar">
        <table class="tabla">
            <tr><th>Producto</th><th>Categoría</th><th class="num">Precio</th><th>Estado</th><th></th></tr>
            @foreach ($productos as $producto)
                <tr>
                    <td><strong>{{ $producto->nombre }}</strong><br><span class="muted">{{ $producto->descripcion }}</span></td>
                    <td>{{ $producto->categoria->nombre }}</td>
                    <td class="num">${{ number_format($producto->precio, 2) }}</td>
                    <td><span class="badge {{ $producto->disponible ? 'badge-libre' : 'badge-sin-enviar' }}">{{ $producto->disponible ? 'Disponible' : 'No disponible' }}</span></td>
                    <td>
                        <div class="acciones">
                            <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-outline btn-sm">Editar</a>
                            <form action="{{ route('admin.productos.destroy', $producto) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar {{ $producto->nombre }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <p class="mt-10"><a href="{{ route('admin.dashboard') }}">← Volver al panel</a></p>
</div>
@endsection
