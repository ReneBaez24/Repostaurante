@if ($paginator->hasPages())
    <nav class="filtros" style="justify-content: space-between; align-items: center;" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="filtro" style="opacity: .4;">← Anterior</span>
        @else
            <a class="filtro" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Anterior</a>
        @endif

        <span class="muted">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} · {{ $paginator->total() }} registros</span>

        @if ($paginator->hasMorePages())
            <a class="filtro" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente →</a>
        @else
            <span class="filtro" style="opacity: .4;">Siguiente →</span>
        @endif
    </nav>
@endif
