@extends('layouts.app')

@section('title', 'Consultar '.$codigo)

@section('content')
<div class="container">
    <div class="main-content">
        <h2>CÓDIGO {{ $codigo }}</h2>

        @if ($existente)
            <div class="alert" style="margin: 0 0 15px; background: var(--warning-bg); color: var(--warning-text);">
                Este código ya está en el inventario como «{{ $existente->nombre }}» ({{ $existente->existencias + 0 }} {{ $existente->unidad }}). Guardarlo actualizará sus datos y existencias.
            </div>
        @endif

        @if ($producto)
            <div class="sidebar" style="width: 100%; display: flex; gap: 20px;">
                @if ($producto->imagenUrl)
                    <img src="{{ $producto->imagenUrl }}" alt="{{ $producto->nombre }}" style="width: 140px; height: 140px; object-fit: contain; background: #fff; border-radius: 6px;">
                @endif
                <div style="flex: 1;">
                    <h3 style="border: none; padding: 0;">{{ $producto->nombre }}</h3>
                    <p class="muted" style="margin-bottom: 10px;">{{ collect([$producto->marca, $producto->cantidad])->filter()->join(' · ') ?: 'Sin marca ni presentación registradas' }}</p>

                    <p class="campo">Alérgenos</p>
                    <p style="margin-bottom: 10px;">
                        @forelse ($producto->alergenos as $alergeno)
                            <span class="badge badge-espera">{{ $alergeno }}</span>
                        @empty
                            <span class="muted">No declarados en Open Food Facts</span>
                        @endforelse
                    </p>

                    <p class="campo">Ingredientes</p>
                    <p style="font-size: 13px; margin-bottom: 10px;">{{ $producto->ingredientes ?? 'No disponibles.' }}</p>

                    @if ($producto->nutriscore || $producto->nutrimentos)
                        <p class="campo">Por 100 g</p>
                        <p style="font-size: 13px;">
                            @if ($producto->nutriscore) Nutri-Score <strong>{{ $producto->nutriscore }}</strong> · @endif
                            @isset($producto->nutrimentos['energia_kcal']) {{ $producto->nutrimentos['energia_kcal'] }} kcal · @endisset
                            @isset($producto->nutrimentos['grasas']) grasas {{ $producto->nutrimentos['grasas'] }} g · @endisset
                            @isset($producto->nutrimentos['azucares']) azúcares {{ $producto->nutrimentos['azucares'] }} g · @endisset
                            @isset($producto->nutrimentos['sal']) sal {{ $producto->nutrimentos['sal'] }} g @endisset
                        </p>
                    @endif
                    <p class="muted mt-10">Fuente: Open Food Facts (datos abiertos, ODbL).</p>
                </div>
            </div>
        @else
            <div class="alert alert-error" style="margin: 0 0 15px;">{{ $aviso }}</div>
            <p class="muted">Puedes registrarlo a mano con el formulario de la derecha.</p>
        @endif

        <p class="mt-10"><a href="{{ route('admin.inventario.index') }}">← Volver al inventario</a></p>
    </div>

    <form class="sidebar" method="POST" action="{{ route('admin.inventario.store') }}">
        @csrf
        <input type="hidden" name="codigo" value="{{ $codigo }}">
        <h3>{{ $producto ? 'AGREGAR AL INVENTARIO' : 'REGISTRO MANUAL' }}</h3>

        @unless ($producto)
            <input type="hidden" name="manual" value="1">
            <label class="campo">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="150">
            @error('nombre') <p class="field-error">{{ $message }}</p> @enderror

            <label class="campo">Marca (opcional)</label>
            <input type="text" name="marca" value="{{ old('marca') }}" maxlength="100">

            <label class="campo">Presentación (opcional)</label>
            <input type="text" name="presentacion" value="{{ old('presentacion') }}" maxlength="60" placeholder="Ej. 1 kg">
        @endunless

        <label class="campo">Existencias</label>
        <input type="number" name="existencias" step="0.01" min="0" value="{{ old('existencias', $existente ? $existente->existencias + 0 : 1) }}" required>
        @error('existencias') <p class="field-error">{{ $message }}</p> @enderror

        <label class="campo">Unidad</label>
        <select name="unidad">
            @foreach (\App\Models\Ingrediente::UNIDADES as $unidad)
                <option value="{{ $unidad }}" @selected(old('unidad', $existente?->unidad ?? 'pieza') === $unidad)>{{ $unidad }}</option>
            @endforeach
        </select>
        @error('unidad') <p class="field-error">{{ $message }}</p> @enderror

        <button class="btn">{{ $existente ? 'Actualizar' : 'Guardar' }}</button>
    </form>
</div>
@endsection
