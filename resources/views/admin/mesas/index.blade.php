@extends('layouts.app')

@section('title', 'Mesas')

@section('content')
<div class="container" style="display: block;">
    <div class="section-head">
        <h2 style="margin: 0;">MESAS</h2>
        <a href="{{ route('admin.mesas.create') }}" class="btn btn-sm">+ Nueva mesa</a>
    </div>

    <div class="sidebar">
        <table class="tabla">
            <tr><th>Mesa</th><th>Sillas</th><th>Zona</th><th>Estado</th><th></th></tr>
            @foreach ($mesas as $mesa)
                @php $estado = $mesa->estado(); @endphp
                <tr>
                    <td><strong>{{ $mesa->numeroFormateado() }}</strong></td>
                    <td>{{ $mesa->capacidad }}</td>
                    <td>{{ $mesa->zona->etiqueta() }}</td>
                    <td>
                        @if ($mesa->activa)
                            <span class="badge {{ $estado->clase() }}">{{ $estado->etiqueta() }}</span>
                        @else
                            <span class="badge badge-sin-enviar">Inactiva</span>
                        @endif
                    </td>
                    <td>
                        <div class="acciones">
                            <a href="{{ route('admin.mesas.edit', $mesa) }}" class="btn btn-outline btn-sm">Editar</a>
                            <form action="{{ route('admin.mesas.destroy', $mesa) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar la mesa {{ $mesa->numero }}?')">
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
