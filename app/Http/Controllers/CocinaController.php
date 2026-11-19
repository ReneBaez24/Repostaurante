<?php

namespace App\Http\Controllers;

use App\Enums\EstadoItem;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pantalla de cocina: lo que se envió desde "Selección de pedidos"
 * aparece aquí "En preparación" hasta que cocina lo marca como "Preparado".
 */
class CocinaController extends Controller
{
    public function index(): View
    {
        // Incluye cuentas ya pagadas que aún tienen algo en preparación
        $pedidos = Pedido::enMesa()
            ->whereHas('items', fn ($q) => $q->where('estado', EstadoItem::EnPreparacion->value))
            ->with(['mesa', 'items' => fn ($q) => $q->where('estado', EstadoItem::EnPreparacion->value)->with('producto')])
            ->orderBy('abierto_at')
            ->get();

        return view('cocina', ['pedidos' => $pedidos]);
    }

    public function preparado(PedidoItem $item): RedirectResponse
    {
        if ($item->estado === EstadoItem::EnPreparacion) {
            $item->update(['estado' => EstadoItem::Preparado, 'preparado_at' => now()]);
        }

        return back()->with('ok', "{$item->cantidad} × {$item->producto->nombre} listo para servir.");
    }

    public function pedidoPreparado(Pedido $pedido): RedirectResponse
    {
        $pedido->items()
            ->where('estado', EstadoItem::EnPreparacion->value)
            ->update(['estado' => EstadoItem::Preparado->value, 'preparado_at' => now()]);

        return back()->with('ok', "Mesa {$pedido->mesa->numero}: todo listo para servir.");
    }
}
