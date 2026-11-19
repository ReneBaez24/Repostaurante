@extends('layouts.app')

@section('title', 'Cocina')

@section('content')
<div class="container" style="display: block;">
    <div class="section-head">
        <h2 style="margin: 0;">COCINA</h2>
        <a href="{{ route('cocina') }}" class="btn btn-outline btn-sm">↻ Actualizar</a>
    </div>

    @if ($pedidos->isEmpty())
        <p class="muted">No hay comandas pendientes.</p>
    @endif

    <div class="mesas-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
        @foreach ($pedidos as $pedido)
            <div class="comanda">
                <h4>
                    <span>MESA {{ $pedido->mesa->numeroFormateado() }}</span>
                    <span class="muted">{{ $pedido->folio() }} · {{ $pedido->minutosAbierto() }} min</span>
                </h4>
                @foreach ($pedido->items as $item)
                    <div class="ticket-line">
                        <span class="cant">{{ $item->cantidad }}</span>
                        <div class="desc">
                            {{ $item->producto->nombre }}
                            @if ($item->notas) <div class="nota"> {{ $item->notas }}</div> @endif
                            <div class="nota">Enviado {{ $item->enviado_at?->format('H:i') }}</div>
                        </div>
                        <form action="{{ route('cocina.preparado', $item) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="btn btn-outline btn-sm">Listo</button>
                        </form>
                    </div>
                @endforeach
                <form action="{{ route('cocina.pedido', $pedido) }}" method="POST" class="mt-10">
                    @csrf @method('PATCH')
                    <button class="btn">Marcar todo como preparado</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Refresca la pantalla de cocina cada 30 s para ver nuevas comandas
    setTimeout(() => location.reload(), 30000);
</script>
@endpush
