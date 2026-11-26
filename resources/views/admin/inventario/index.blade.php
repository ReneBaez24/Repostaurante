@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div class="container">
    <div class="main-content">
        <div class="section-head">
            <h2 style="margin: 0;">INVENTARIO</h2>
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="text" name="q" value="{{ $buscar }}" placeholder="Buscar nombre, marca o código" style="margin: 0; width: 240px;">
                <button class="btn btn-outline btn-sm">Buscar</button>
            </form>
        </div>

        <div class="sidebar" style="width: 100%;">
            <table class="tabla">
                <tr><th>Ingrediente</th><th>Código</th><th>Alérgenos</th><th class="num">Existencias</th><th></th></tr>
                @forelse ($ingredientes as $ingrediente)
                    <tr>
                        <td>
                            <strong>{{ $ingrediente->nombre }}</strong>
                            @if ($ingrediente->nutriscore) <span class="badge badge-ocupada">Nutri-Score {{ $ingrediente->nutriscore }}</span> @endif
                            <br><span class="muted">{{ collect([$ingrediente->marca, $ingrediente->presentacion])->filter()->join(' · ') }}{{ $ingrediente->origen === 'manual' ? ' · captura manual' : '' }}</span>
                        </td>
                        <td>{{ $ingrediente->codigo_barras }}</td>
                        <td>
                            @forelse ($ingrediente->alergenos ?? [] as $alergeno)
                                <span class="badge badge-espera">{{ $alergeno }}</span>
                            @empty
                                <span class="muted">—</span>
                            @endforelse
                        </td>
                        <td class="num">
                            <form action="{{ route('admin.inventario.update', $ingrediente) }}" method="POST" style="display: flex; gap: 4px; justify-content: flex-end;">
                                @csrf @method('PATCH')
                                <input type="number" name="existencias" step="0.01" min="0" value="{{ $ingrediente->existencias + 0 }}" style="width: 80px; margin: 0; padding: 4px;">
                                <select name="unidad" style="width: 90px; margin: 0; padding: 4px;">
                                    @foreach (\App\Models\Ingrediente::UNIDADES as $unidad)
                                        <option value="{{ $unidad }}" @selected($ingrediente->unidad === $unidad)>{{ $unidad }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline btn-sm" title="Guardar existencias">✓</button>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.inventario.destroy', $ingrediente) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar {{ $ingrediente->nombre }} del inventario?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">{{ $buscar ? 'Sin resultados.' : 'El inventario está vacío. Agrega un ingrediente con su código de barras.' }}</td></tr>
                @endforelse
            </table>
            <div class="mt-10">{{ $ingredientes->links('partials.paginacion') }}</div>
        </div>
        <p class="mt-10"><a href="{{ route('admin.dashboard') }}">← Volver al panel</a></p>
    </div>

    <form class="sidebar" method="GET" action="{{ route('admin.inventario.consultar') }}">
        <h3>AGREGAR POR CÓDIGO DE BARRAS</h3>
        <p class="muted" style="margin-bottom: 15px;">Escribe o escanea el código del empaque. Los datos se consultan en Open Food Facts.</p>

        <label class="campo">Código de barras</label>
        <input type="text" name="codigo" value="{{ old('codigo') }}" inputmode="numeric" autocomplete="off" autofocus
               placeholder="Ej. 7501055363278" maxlength="30">
        @error('codigo') <p class="field-error">{{ $message }}</p> @enderror
        <p class="muted" style="margin: -8px 0 15px;">8, 12, 13 o 14 dígitos (EAN / UPC).</p>

        <button class="btn">Consultar</button>
    </form>
</div>
@endsection
