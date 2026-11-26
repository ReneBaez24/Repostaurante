<?php

namespace App\Http\Controllers;

use App\Enums\EstadoItem;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Support\CalculadoraCuenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CobroController extends Controller
{
    /** Pantalla 3: Sección de cobro. */
    public function show(Pedido $pedido): View|RedirectResponse
    {
        if (! $pedido->estaAbierto()) {
            return redirect()->route('overview')->with('error', "El pedido {$pedido->folio()} ya está cerrado.");
        }

        $pedido->load(['mesa', 'items.producto']);
        $consumo = $pedido->total();

        return view('checkout', [
            'pedido' => $pedido,
            'consumo' => $consumo,
            'impuesto' => self::impuestoIncluido($consumo),
            'tasaImpuesto' => config('restaurante.impuesto'),
            'propinas' => config('restaurante.propinas'),
            'metodos' => MetodoPago::cases(),
        ]);
    }

    /** "Cobrar $X y cerrar mesa". */
    public function store(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::enum(MetodoPago::class)],
            'propina' => ['required', 'integer', Rule::in(config('restaurante.propinas'))],
        ]);

        $resultado = DB::transaction(function () use ($pedido, $datos, $request) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->with('items')->first();

            if (! $pedido->estaAbierto()) {
                return 'Este pedido ya fue cobrado.';
            }
            if ($pedido->items->isEmpty()) {
                return 'La cuenta está vacía. Si la mesa se abrió por error, usa «Cancelar pedido».';
            }
            if ($pedido->items->contains('estado', EstadoItem::SinEnviar)) {
                return 'Hay productos sin enviar a cocina. Envíalos o quítalos antes de cobrar.';
            }

            // Los importes se recalculan en el servidor; nunca se confía en lo que manda el navegador
            $consumo = $pedido->total();
            $propina = CalculadoraCuenta::propina($consumo, (int) $datos['propina']);

            $pago = Pago::create([
                'pedido_id' => $pedido->id,
                'user_id' => $request->user()->id,
                'metodo' => $datos['metodo'],
                'consumo' => $consumo,
                'impuesto' => self::impuestoIncluido($consumo),
                'propina_porcentaje' => $datos['propina'],
                'propina' => $propina,
                'total' => CalculadoraCuenta::total($consumo, (int) $datos['propina']),
            ]);

            $pedido->update(['estado' => EstadoPedido::Pagado, 'cerrado_at' => now()]);

            return $pago;
        });

        if (is_string($resultado)) {
            return redirect()->route('checkout', $pedido)->with('error', $resultado);
        }

        // La mesa sigue ocupada hasta que el mesero pulse "Liberar mesa" en la vista general
        return redirect()->route('overview', ['mesa' => $pedido->mesa_id])
            ->with('ok', "Mesa {$pedido->mesa->numero} cobrada ($".number_format((float) $resultado->total, 2).'). Libérala cuando los clientes se retiren.');
    }

    /** Los precios ya incluyen impuesto (ver App\Support\CalculadoraCuenta). */
    public static function impuestoIncluido(float $total): float
    {
        return CalculadoraCuenta::impuestoIncluido($total, (float) config('restaurante.impuesto'));
    }
}
