@extends('layouts.app')

@section('title', $producto->exists ? 'Editar producto' : 'Nuevo producto')

@section('content')
<div style="display: flex; justify-content: center; padding: 30px;">
    <form class="sidebar" style="width: 450px; flex: none;" method="POST"
          action="{{ $producto->exists ? route('admin.productos.update', $producto) : route('admin.productos.store') }}">
        @csrf
        @if ($producto->exists) @method('PUT') @endif

        <h3>{{ $producto->exists ? 'EDITAR PRODUCTO' : 'NUEVO PRODUCTO' }}</h3>

        <label class="campo">Categoría</label>
        <select name="categoria_id" required>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $producto->categoria_id) === $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
        </select>

        <label class="campo">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required maxlength="100">

        <label class="campo">Descripción</label>
        <input type="text" name="descripcion" value="{{ old('descripcion', $producto->descripcion) }}" maxlength="255">

        <label class="campo">Precio (impuesto incluido)</label>
        <input type="number" name="precio" step="0.01" min="0" value="{{ old('precio', $producto->precio) }}" required>

        <label class="check">
            <input type="checkbox" name="disponible" value="1" @checked(old('disponible', $producto->disponible))> Disponible en el menú
        </label>

        <button class="btn">Guardar</button>
        <a href="{{ route('admin.productos.index') }}" class="btn btn-outline btn-block mt-10">Cancelar</a>
    </form>
</div>
@endsection
