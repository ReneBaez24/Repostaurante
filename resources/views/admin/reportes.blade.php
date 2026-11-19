@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="container">
    <div class="main-content">
        <div class="section-head">
            <h2 style="margin: 0;">VENTAS DEL {{ $fecha->format('d/m/Y') }}</h2>
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" style="margin: 0;">
                <button class="btn btn-sm">Ver</button>
            </form>
        </div>

        <div class="sidebar" style="width: 100%;">
            <table class="tabla">
                <tr>
                    <th>Hora</th><th>Pedido</th><th>Mesa</th><th>Cobró</th><th>Método</th>
                    <th class="num">Consumo</th><th class="num">Propina</th><th class="num">Total</th>
                </tr>
                @forelse ($pagos as $pago)
                    <tr>
                        <td>{{ $pago->created_at->format('H:i') }}</td>
                        <td>{{ $pago->pedido->folio() }}</td>
                        <td>{{ $pago->pedido->mesa->numeroFormateado() }}</td>
                        <td>{{ $pago->cajero->name }}</td>
                        <td>{{ $pago->metodo->etiqueta() }}</td>
                        <td class="num">${{ number_format($pago->consumo, 2) }}</td>
                        <td class="num">${{ number_format($pago->propina, 2) }}</td>
                        <td class="num">${{ number_format($pago->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No hay ventas registradas este día.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="sidebar">
        <h3>RESUMEN</h3>
        <div class="fila"><span>Cuentas cobradas</span><strong>{{ $pagos->count() }}</strong></div>
        <div class="fila"><span>Consumo</span><strong>${{ number_format($consumo, 2) }}</strong></div>
        <div class="fila muted"><span>Impuesto incluido</span><span>${{ number_format($impuestos, 2) }}</span></div>
        <div class="fila"><span>Propinas</span><strong>${{ number_format($propinas, 2) }}</strong></div>
        <div class="fila" style="font-size: 18px; margin-top: 8px;"><span>TOTAL</span><strong>${{ number_format($total, 2) }}</strong></div>

        <h3 class="mt-20">POR MÉTODO</h3>
        @foreach ($porMetodo as $metodo => $monto)
            <div class="fila"><span>{{ $metodo }}</span><strong>${{ number_format($monto, 2) }}</strong></div>
        @endforeach

        <h3 class="mt-20">MÁS VENDIDOS</h3>
        @forelse ($masVendidos as $fila)
            <div class="fila"><span>{{ $fila->unidades }} × {{ $fila->producto->nombre }}</span><span>${{ number_format($fila->importe, 2) }}</span></div>
        @empty
            <p class="muted">Sin datos.</p>
        @endforelse
    </div>
</div>
@endsection
