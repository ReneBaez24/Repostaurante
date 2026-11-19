@extends('layouts.app')

@section('title', 'Cobro mesa '.$pedido->mesa->numeroFormateado())

@section('content')
@php
    $sinEnviar = $pedido->unidadesSinEnviar();
    $propinaDefault = (int) old('propina', in_array(10, $propinas) ? 10 : $propinas[0]);
@endphp
<div class="container">
    <div class="main-content">
        <h2>SECCIÓN DE COBRO</h2>

        <div class="sidebar" style="flex: 1; width: 100%;">
            <h3>DETALLE DE LA CUENTA <span style="float:right; color: #888;">{{ $pedido->folio() }}</span></h3>
            <p style="font-size: 12px; margin-bottom: 20px;">
                Mesa {{ $pedido->mesa->numeroFormateado() }} • {{ $pedido->comensales }} comensales • Abierta a las {{ $pedido->abierto_at->format('H:i') }}
            </p>

            @if ($sinEnviar > 0)
                <div class="alert alert-error" style="margin: 0 0 15px;">
                    Hay {{ $sinEnviar }} {{ $sinEnviar === 1 ? 'producto' : 'productos' }} sin enviar a cocina. Envíalos o quítalos antes de cobrar.
                </div>
            @elseif ($pedido->todoPreparado())
                <div class="alert alert-ok" style="margin: 0 0 15px;">✓ Todo preparado <span style="float:right; font-size: 12px;">{{ $pedido->unidades() }} unidades listas para servir</span></div>
            @elseif ($pedido->items->isNotEmpty())
                <div class="alert" style="margin: 0 0 15px; background: var(--warning-bg); color: var(--warning-text);">Cocina aún está preparando parte del pedido.</div>
            @endif

            <table class="tabla">
                <tr>
                    <th>PRODUCTO</th>
                    <th class="num">CANT.</th>
                    <th class="num">PRECIO</th>
                    <th class="num">IMPORTE</th>
                </tr>
                @forelse ($pedido->items as $item)
                    <tr>
                        <td>{{ $item->producto->nombre }}</td>
                        <td class="num">{{ $item->cantidad }}</td>
                        <td class="num">${{ number_format($item->precio_unitario, 2) }}</td>
                        <td class="num">${{ number_format($item->importe(), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">La cuenta está vacía.</td></tr>
                @endforelse
            </table>

            <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <p class="fila">Subtotal <span>${{ number_format($consumo, 2) }}</span></p>
                <p class="fila muted">Impuesto incluido ({{ round($tasaImpuesto * 100) }}%) <span>${{ number_format($impuesto, 2) }}</span></p>
                <h3 style="margin-top: 15px; border: none;">TOTAL DEL CONSUMO <span style="float:right;">${{ number_format($consumo, 2) }}</span></h3>
            </div>
            <a href="{{ route('order', $pedido) }}" style="font-size: 12px; color: var(--primary-color); float: right;">← Volver al pedido</a>
        </div>
    </div>

    <!-- Panel de Pago -->
    <form class="sidebar" action="{{ route('checkout.store', $pedido) }}" method="POST" id="form-cobro">
        @csrf
        <h3>COBRAR Y CERRAR</h3>
        <p style="font-size: 12px; margin-bottom: 20px;">Selecciona el método de pago.</p>

        <p>Método de pago</p>
        <div class="opciones">
            @foreach ($metodos as $metodo)
                <label class="opcion">
                    <input type="radio" name="metodo" value="{{ $metodo->value }}" @checked(old('metodo', 'tarjeta') === $metodo->value)>
                    <span>{{ $metodo === \App\Enums\MetodoPago::Tarjeta ? '' : '' }} {{ $metodo->etiqueta() }}</span>
                </label>
            @endforeach
        </div>

        <p>Propina opcional</p>
        <div class="opciones">
            @foreach ($propinas as $p)
                <label class="opcion">
                    <input type="radio" name="propina" value="{{ $p }}" @checked($propinaDefault === $p)>
                    <span>{{ $p === 0 ? 'Sin propina' : $p.' %' }}</span>
                </label>
            @endforeach
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-bottom: 20px;">
            <p class="fila">Consumo <span>${{ number_format($consumo, 2) }}</span></p>
            <p class="fila"><span>Propina (<span id="propina-pct">{{ $propinaDefault }}</span> %)</span> <span id="propina-monto"></span></p>
            <p class="muted" style="margin-top: 10px;">TOTAL A COBRAR</p>
            <h2 id="total" style="margin-top: 4px;"></h2>
        </div>

        <button type="submit" class="btn" @disabled($sinEnviar > 0 || $pedido->items->isEmpty())>
            Cobrar <span id="total-boton"></span>
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Recalcula propina y total en pantalla (el servidor vuelve a calcular al cobrar)
    (function () {
        const consumo = {{ json_encode($consumo) }};
        const dinero = n => '$' + n.toFixed(2);
        function actualizar() {
            const pct = parseInt(document.querySelector('input[name=propina]:checked').value, 10);
            const propina = Math.round(consumo * pct) / 100;
            const total = consumo + propina;
            document.getElementById('propina-pct').textContent = pct;
            document.getElementById('propina-monto').textContent = dinero(propina);
            document.getElementById('total').textContent = dinero(total);
            document.getElementById('total-boton').textContent = dinero(total);
        }
        document.querySelectorAll('input[name=propina]').forEach(r => r.addEventListener('change', actualizar));
        actualizar();
    })();
</script>
@endpush
