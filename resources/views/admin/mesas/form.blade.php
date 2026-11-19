@extends('layouts.app')

@section('title', $mesa->exists ? 'Editar mesa' : 'Nueva mesa')

@section('content')
<div style="display: flex; justify-content: center; padding: 30px;">
    <form class="sidebar" style="width: 400px; flex: none;" method="POST"
          action="{{ $mesa->exists ? route('admin.mesas.update', $mesa) : route('admin.mesas.store') }}">
        @csrf
        @if ($mesa->exists) @method('PUT') @endif

        <h3>{{ $mesa->exists ? 'EDITAR MESA' : 'NUEVA MESA' }}</h3>

        <label class="campo">Número</label>
        <input type="number" name="numero" min="1" value="{{ old('numero', $mesa->numero) }}" required>

        <label class="campo">Capacidad (sillas)</label>
        <input type="number" name="capacidad" min="1" max="30" value="{{ old('capacidad', $mesa->capacidad) }}" required>

        <label class="campo">Zona</label>
        <select name="zona">
            @foreach ($zonas as $zona)
                <option value="{{ $zona->value }}" @selected(old('zona', $mesa->zona?->value) === $zona->value)>{{ $zona->etiqueta() }}</option>
            @endforeach
        </select>

        <label class="check">
            <input type="checkbox" name="activa" value="1" @checked(old('activa', $mesa->activa))> Mesa activa (aparece en la vista general)
        </label>

        <button class="btn">Guardar</button>
        <a href="{{ route('admin.mesas.index') }}" class="btn btn-outline btn-block mt-10">Cancelar</a>
    </form>
</div>
@endsection
