<?php

namespace App\Http\Controllers;

use App\Enums\EstadoMesa;
use App\Enums\ZonaMesa;
use App\Models\Mesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MesaController extends Controller
{
    /** Pantalla 1: Vista general de mesas. */
    public function index(Request $request): View
    {
        $mesas = Mesa::activas()
            ->with(['pedidoActual.items', 'pedidoActual.pago'])
            ->orderBy('numero')
            ->get();

        $libres = $mesas->filter(fn (Mesa $m) => $m->estado() === EstadoMesa::Libre);
        $filtro = $request->query('filtro') === 'libres' ? 'libres' : 'todas';

        $seleccionada = $mesas->firstWhere('id', (int) $request->query('mesa'))
            ?? $libres->first()
            ?? $mesas->first();

        return view('overview', [
            'mesas' => $filtro === 'libres' ? $libres : $mesas,
            'totalMesas' => $mesas->count(),
            'totalLibres' => $libres->count(),
            'filtro' => $filtro,
            'seleccionada' => $seleccionada,
            'zonas' => ZonaMesa::cases(),
        ]);
    }

    /** "Liberar mesa": los clientes ya pagaron y se retiraron. */
    public function liberar(Mesa $mesa): RedirectResponse
    {
        $liberada = DB::transaction(function () use ($mesa) {
            $pedido = $mesa->pedidos()->enMesa()->lockForUpdate()->latest('id')->first();

            if (! $pedido || ! $pedido->estaPagado()) {
                return false;
            }

            $pedido->update(['liberada_at' => now()]);

            return true;
        });

        if (! $liberada) {
            return redirect()->route('overview', ['mesa' => $mesa->id])
                ->with('error', 'Sólo se puede liberar una mesa después de cobrar la cuenta.');
        }

        return redirect()->route('overview', ['mesa' => $mesa->id])
            ->with('ok', "Mesa {$mesa->numero} liberada.");
    }
}
