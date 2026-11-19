@extends('layouts.app')

@section('title', 'Mesas')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>VISTA GENERAL</h2>

        <div class="section-head">
            <h4>MESAS DISPONIBLES</h4>
            <div class="filtros" style="margin: 0;">
                <a href="{{ route('overview', ['mesa' => $seleccionada?->id]) }}" @class(['filtro', 'activo' => $filtro === 'todas'])>Todas · {{ $totalMesas }}</a>
                <a href="{{ route('overview', ['filtro' => 'libres', 'mesa' => $seleccionada?->id]) }}" @class(['filtro', 'activo' => $filtro === 'libres'])>Libres · {{ $totalLibres }}</a>
            </div>
        </div>

        <div class="mesas-grid">
            @forelse ($mesas as $mesa)
                @php
                    $estado = $mesa->estado();
                    $pedido = $mesa->pedidoActual;
                @endphp
                <a href="{{ route('overview', array_filter(['mesa' => $mesa->id, 'filtro' => $filtro === 'libres' ? 'libres' : null])) }}"
                   @class([
                       'mesa-card',
                       'seleccionada' => $seleccionada?->is($mesa),
                       'ocupada' => $estado === \App\Enums\EstadoMesa::Ocupada,
                       'esperando' => $estado === \App\Enums\EstadoMesa::EsperandoOrden,
                       'abierta' => $estado === \App\Enums\EstadoMesa::Abierta,
                   ])>
                    <span class="status {{ $estado->clase() }}">{{ $estado->etiqueta() }}</span>
                    <p>MESA</p>
                    <div class="number">{{ $mesa->numeroFormateado() }}</div>
                    <div class="details">
                        @if ($pedido)
                            {{ $pedido->comensales }} comensales · {{ $pedido->estaPagado() ? 'Pagada' : $pedido->minutosAbierto().' min' }}
                        @else
                            {{ $mesa->capacidad }} sillas
                        @endif
                    </div>
                </a>
            @empty
                <p class="muted">No hay mesas {{ $filtro === 'libres' ? 'libres' : 'registradas' }}.</p>
            @endforelse
        </div>
    </div>

    <!-- panel lateral de la mesa -->
    @if ($seleccionada)
        @php
            $estado = $seleccionada->estado();
            $pedido = $seleccionada->pedidoActual;
        @endphp
        <div class="sidebar">
            <h3>MESA SELECCIONADA <span class="badge {{ $estado->clase() }}" style="float:right;">{{ $estado->etiqueta() }}</span></h3>
            <h2>MESA {{ $seleccionada->numeroFormateado() }}</h2>
            <p style="font-size: 12px; margin-bottom: 20px;">{{ $seleccionada->zona->etiqueta() }} • Capacidad para {{ $seleccionada->capacidad }} personas</p>

            @if (! $pedido)
                <form action="{{ route('mesas.abrir', $seleccionada) }}" method="POST">
                    @csrf
                    <p>¿Cuántos comensales?</p>
                    <div class="stepper">
                        <button type="button" class="btn btn-outline" data-step="-1">-</button>
                        <input type="text" inputmode="numeric" name="comensales" id="comensales"
                               value="{{ old('comensales', min(2, $seleccionada->capacidad)) }}"
                               data-max="{{ $seleccionada->capacidad }}">
                        <button type="button" class="btn btn-outline" data-step="1">+</button>
                    </div>

                    <p>Zona</p>
                    <select name="zona">
                        @foreach ($zonas as $zona)
                            <option value="{{ $zona->value }}" @selected($seleccionada->zona === $zona)>{{ $zona->etiqueta() }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn mt-20">Abrir mesa y agregar pedido →</button>
                </form>
            @elseif ($pedido->estaPagado())
                <div class="alert alert-ok" style="margin: 0 0 15px;">✓ Cuenta pagada</div>
                <div class="fila"><span>Pedido</span><strong>{{ $pedido->folio() }}</strong></div>
                <div class="fila"><span>Comensales</span><strong>{{ $pedido->comensales }}</strong></div>
                <div class="fila"><span>Cobrada</span><strong>{{ $pedido->cerrado_at->format('H:i') }}</strong></div>
                <div class="fila"><span>Total pagado</span><strong>${{ number_format((float) $pedido->pago?->total, 2) }} · {{ $pedido->pago?->metodo->etiqueta() }}</strong></div>
                <p class="muted mt-10">Libera la mesa cuando los clientes se retiren.</p>

                <form action="{{ route('mesas.liberar', $seleccionada) }}" method="POST"
                      onsubmit="return confirm('¿Liberar la mesa {{ $seleccionada->numero }}?')">
                    @csrf
                    <button class="btn mt-20">Liberar mesa</button>
                </form>
            @else
                <div class="fila"><span>Pedido</span><strong>{{ $pedido->folio() }}</strong></div>
                <div class="fila"><span>Comensales</span><strong>{{ $pedido->comensales }}</strong></div>
                <div class="fila"><span>Abierta</span><strong>{{ $pedido->abierto_at->format('H:i') }} ({{ $pedido->minutosAbierto() }} min)</strong></div>
                <div class="fila"><span>Total actual</span><strong>${{ number_format($pedido->total(), 2) }}</strong></div>

                <a href="{{ route('order', $pedido) }}" class="btn btn-block mt-20">Ver pedido →</a>
                <a href="{{ route('checkout', $pedido) }}" class="btn btn-outline btn-block mt-10">Ver cuenta</a>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Botones − / + de comensales (limitados a la capacidad de la mesa)
    document.querySelectorAll('[data-step]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById('comensales');
            const max = parseInt(input.dataset.max, 10);
            const valor = (parseInt(input.value, 10) || 0) + parseInt(btn.dataset.step, 10);
            input.value = Math.min(Math.max(valor, 1), max);
        });
    });
</script>
@endpush
