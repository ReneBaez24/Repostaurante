<?php

namespace App\Http\Controllers;

use App\Enums\EstadoItem;
use App\Enums\EstadoPedido;
use App\Enums\ZonaMesa;
use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    /** "Abrir mesa y agregar pedido" desde la vista general. */
    public function abrir(Request $request, Mesa $mesa): RedirectResponse
    {
        abort_unless($mesa->activa, 404);

        $datos = $request->validate([
            'comensales' => ['required', 'integer', 'min:1', 'max:'.max($mesa->capacidad, 1)],
            'zona' => ['required', Rule::enum(ZonaMesa::class)],
        ], [
            'comensales.max' => "La mesa {$mesa->numero} tiene capacidad para {$mesa->capacidad} personas.",
        ]);

        $pedido = DB::transaction(function () use ($mesa, $datos, $request) {
            // Bloquea la mesa para que dos meseros no la abran al mismo tiempo
            $mesa = Mesa::whereKey($mesa->id)->lockForUpdate()->first();

            // Si la mesa ya tiene un pedido (abierto o pagado sin liberar) no se abre otro
            $actual = $mesa->pedidos()->enMesa()->latest('id')->first();
            if ($actual) {
                return $actual;
            }

            $mesa->update(['zona' => $datos['zona']]);

            return $mesa->pedidos()->create([
                'user_id' => $request->user()->id,
                'comensales' => $datos['comensales'],
                'estado' => EstadoPedido::Abierto,
                'abierto_at' => now(),
            ]);
        });

        if ($pedido->estaPagado()) {
            return redirect()->route('overview', ['mesa' => $mesa->id])
                ->with('error', "La mesa {$mesa->numero} ya pagó. Libérala antes de abrirla de nuevo.");
        }

        return redirect()->route('order', $pedido);
    }

    /** Pantalla 2: Selección de pedidos. */
    public function show(Pedido $pedido): View|RedirectResponse
    {
        if (! $pedido->estaAbierto()) {
            return redirect()->route('overview')->with('error', "El pedido {$pedido->folio()} ya está cerrado.");
        }

        $pedido->load(['mesa', 'items.producto']);

        return view('order', [
            'pedido' => $pedido,
            'categorias' => Categoria::orderBy('orden')->get(),
            'productos' => Producto::disponibles()->with('categoria')->orderBy('categoria_id')->orderBy('nombre')->get(),
        ]);
    }

    /** Botón "+" de una tarjeta de producto. */
    public function agregar(Pedido $pedido, Producto $producto): RedirectResponse
    {
        $this->asegurarAbierto($pedido);
        abort_unless($producto->disponible, 422, 'Producto no disponible.');

        // Se suma al renglón que todavía no se envía a cocina; si no hay, se crea uno nuevo
        $item = $pedido->items()
            ->where('producto_id', $producto->id)
            ->where('estado', EstadoItem::SinEnviar->value)
            ->first();

        if ($item) {
            $item->increment('cantidad');
        } else {
            $pedido->items()->create([
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'precio_unitario' => $producto->precio,
                'estado' => EstadoItem::SinEnviar,
            ]);
        }

        return $this->volver($pedido, $producto);
    }

    /** Botón "−": sólo se puede quitar lo que aún no se envía a cocina. */
    public function quitar(Pedido $pedido, Producto $producto): RedirectResponse
    {
        $this->asegurarAbierto($pedido);

        $item = $pedido->items()
            ->where('producto_id', $producto->id)
            ->where('estado', EstadoItem::SinEnviar->value)
            ->first();

        if (! $item) {
            return $this->volver($pedido, $producto)
                ->with('error', "«{$producto->nombre}» ya se envió a cocina y no se puede quitar.");
        }

        $item->cantidad > 1 ? $item->decrement('cantidad') : $item->delete();

        return $this->volver($pedido, $producto);
    }

    /** Nota de un renglón ("Salsa BBQ aparte"). */
    public function notas(Request $request, PedidoItem $item): RedirectResponse
    {
        $this->asegurarAbierto($item->pedido);
        abort_unless($item->estado === EstadoItem::SinEnviar, 422, 'Este producto ya se envió a cocina.');

        $datos = $request->validate(['notas' => ['nullable', 'string', 'max:255']]);
        $item->update(['notas' => $datos['notas'] ?: null]);

        return redirect()->route('order', $item->pedido_id);
    }

    /** "Enviar a cocina · N nuevos". */
    public function enviarCocina(Pedido $pedido): RedirectResponse
    {
        $this->asegurarAbierto($pedido);

        $enviados = $pedido->items()
            ->where('estado', EstadoItem::SinEnviar->value)
            ->update(['estado' => EstadoItem::EnPreparacion->value, 'enviado_at' => now()]);

        return redirect()->route('order', $pedido)->with(
            $enviados ? 'ok' : 'error',
            $enviados ? 'Pedido enviado a cocina.' : 'No hay productos nuevos para enviar.',
        );
    }

    /** "Cancelar pedido": descarta el pedido y deja la mesa libre (sólo si nada se ha enviado a cocina). */
    public function cancelar(Pedido $pedido): RedirectResponse
    {
        $this->asegurarAbierto($pedido);

        $enviados = $pedido->items()->where('estado', '!=', EstadoItem::SinEnviar->value)->exists();
        if ($enviados) {
            return back()->with('error', 'No se puede cancelar: ya hay productos enviados a cocina. Cobra la cuenta para cerrar la mesa.');
        }

        DB::transaction(function () use ($pedido) {
            $pedido->items()->delete();
            $pedido->update(['estado' => EstadoPedido::Cancelado, 'cerrado_at' => now()]);
        });

        return redirect()->route('overview', ['mesa' => $pedido->mesa_id])
            ->with('ok', "Pedido {$pedido->folio()} cancelado. La mesa {$pedido->mesa->numero} quedó libre.");
    }

    private function asegurarAbierto(Pedido $pedido): void
    {
        abort_unless($pedido->estaAbierto(), 422, 'El pedido ya está cerrado.');
    }

    private function volver(Pedido $pedido, Producto $producto): RedirectResponse
    {
        // Regresa a la tarjeta del producto para no perder el scroll
        return redirect()->to(route('order', $pedido).'#producto-'.$producto->id);
    }
}
