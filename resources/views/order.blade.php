@extends('layouts.app')

@section('title', 'Mesa '.$pedido->mesa->numeroFormateado())

@section('content')
<div class="container">
    <div class="main-content">
        <h2>SELECCIÓN DE PEDIDOS</h2>

        <input type="text" id="buscar" placeholder="🔍 Buscar pollo, guarnición o bebida..." autocomplete="off">

        <div class="filtros">
            <button type="button" class="filtro activo" data-categoria="">Todos</button>
            @foreach ($categorias as $categoria)
                <button type="button" class="filtro" data-categoria="{{ $categoria->slug }}">{{ $categoria->nombre }}</button>
            @endforeach
        </div>

        <div class="productos-grid">
            @foreach ($productos as $producto)
                @php $cantidad = $pedido->cantidadDe($producto->id); @endphp
                <div class="producto-card" id="producto-{{ $producto->id }}"
                     data-categoria="{{ $producto->categoria->slug }}"
                     data-nombre="{{ \Illuminate\Support\Str::lower($producto->nombre.' '.$producto->descripcion.' '.$producto->categoria->nombre) }}">
                    <p style="font-size: 9px; text-transform: uppercase; margin-bottom: 4px;">{{ $producto->categoria->nombre }}</p>
                    <h4>{{ $producto->nombre }}</h4>
                    <p>{{ $producto->descripcion }}</p>
                    <div class="producto-footer">
                        <span class="price">${{ number_format($producto->precio, 2) }}</span>
                        <div class="mini-stepper">
                            @if ($cantidad > 0)
                                <form action="{{ route('pedidos.quitar', [$pedido, $producto]) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-outline" title="Quitar uno">-</button>
                                </form>
                                <span class="qty">{{ $cantidad }}</span>
                            @endif
                            <form action="{{ route('pedidos.agregar', [$pedido, $producto]) }}" method="POST">
                                @csrf
                                <button class="btn btn-outline" title="Agregar uno">+</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p id="sin-resultados" class="muted oculto">No se encontraron productos.</p>
    </div>

    <!-- Panel del Ticket -->
    <div class="sidebar">
        <h3>MESA {{ $pedido->mesa->numeroFormateado() }} <span class="badge badge-libre" style="float:right;">{{ $pedido->estado->etiqueta() }}</span></h3>
        <p style="font-size: 12px; margin-bottom: 10px;">Pedido {{ $pedido->folio() }} • {{ $pedido->comensales }} comensales</p>

        @forelse ($pedido->items as $item)
            <div class="ticket-line">
                <span class="cant">{{ $item->cantidad }}</span>
                <div class="desc">
                    {{ $item->producto->nombre }}<br>
                    <span class="badge {{ $item->estado->clase() }}">{{ $item->estado->etiqueta() }}</span>
                    @if ($item->estado === \App\Enums\EstadoItem::SinEnviar)
                        <form action="{{ route('pedidos.notas', $item) }}" method="POST" class="nota-form">
                            @csrf @method('PATCH')
                            <input type="text" name="notas" value="{{ $item->notas }}" placeholder="Nota (ej. salsa aparte)" maxlength="255">
                            <button class="btn btn-outline btn-sm">✓</button>
                        </form>
                    @elseif ($item->notas)
                        <div class="nota">{{ $item->notas }}</div>
                    @endif
                </div>
                <span class="importe">${{ number_format($item->importe(), 2) }}</span>
            </div>
        @empty
            <p class="muted" style="padding: 20px 0;">Aún no hay productos. Agrégalos con el botón «+».</p>
        @endforelse

        <div class="ticket-total">
            <span>{{ $pedido->unidades() }} unidades · Total de la cuenta</span>
            <strong>${{ number_format($pedido->total(), 2) }}</strong>
        </div>

        @php $nuevos = $pedido->unidadesSinEnviar(); @endphp
        <form action="{{ route('pedidos.enviar', $pedido) }}" method="POST">
            @csrf
            <button class="btn" style="margin-bottom: 10px;" @disabled($nuevos === 0)>
                Enviar a cocina • {{ $nuevos }} {{ $nuevos === 1 ? 'nuevo' : 'nuevos' }}
            </button>
        </form>
        <a href="{{ route('checkout', $pedido) }}" class="btn btn-outline btn-block">Ver cuenta</a>

        @unless ($pedido->items->contains(fn ($i) => $i->estado !== \App\Enums\EstadoItem::SinEnviar))
            <form action="{{ route('pedidos.cancelar', $pedido) }}" method="POST" class="mt-10"
                  onsubmit="return confirm('¿Cancelar este pedido? La mesa quedará libre.')">
                @csrf
                <button class="btn btn-danger">Cancelar pedido</button>
            </form>
        @endunless
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Búsqueda y filtro por categoría (en el navegador, sin recargar)
    (function () {
        const buscar = document.getElementById('buscar');
        const filtros = document.querySelectorAll('[data-categoria].filtro');
        const tarjetas = document.querySelectorAll('.producto-card');
        const vacio = document.getElementById('sin-resultados');
        let categoria = '';

        function aplicar() {
            const texto = buscar.value.trim().toLowerCase();
            let visibles = 0;
            tarjetas.forEach(t => {
                const ok = (!categoria || t.dataset.categoria === categoria) && t.dataset.nombre.includes(texto);
                t.classList.toggle('oculto', !ok);
                if (ok) visibles++;
            });
            vacio.classList.toggle('oculto', visibles > 0);
        }

        filtros.forEach(btn => btn.addEventListener('click', () => {
            filtros.forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            categoria = btn.dataset.categoria;
            aplicar();
        }));
        buscar.addEventListener('input', aplicar);
    })();
</script>
@endpush
