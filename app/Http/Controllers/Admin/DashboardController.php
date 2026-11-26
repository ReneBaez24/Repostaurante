<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MetodoPago;
use App\Http\Controllers\Controller;
use App\Models\Ingrediente;
use App\Models\Mesa;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $pagosHoy = Pago::whereDate('created_at', today())->get();

        return view('admin', [
            'usuarios' => User::count(),
            'productos' => Producto::count(),
            'ingredientes' => Ingrediente::count(),
            'mesas' => Mesa::activas()->count(),
            'mesasAbiertas' => Pedido::enMesa()->count(),
            'ventasHoy' => (float) $pagosHoy->sum('total'),
            'cuentasHoy' => $pagosHoy->count(),
        ]);
    }

    /** Reporte de ventas de un día (por defecto hoy). */
    public function reportes(Request $request): View
    {
        $fecha = $request->date('fecha') ?? today();
        $fecha = Carbon::parse($fecha)->startOfDay();

        $pagos = Pago::whereDate('created_at', $fecha)
            ->with(['pedido.mesa', 'cajero'])
            ->orderBy('created_at')
            ->get();

        $porMetodo = collect(MetodoPago::cases())->mapWithKeys(fn (MetodoPago $m) => [
            $m->etiqueta() => (float) $pagos->where('metodo', $m)->sum('total'),
        ]);

        $masVendidos = PedidoItem::query()
            ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(cantidad * precio_unitario) as importe')
            ->whereIn('pedido_id', $pagos->pluck('pedido_id'))
            ->groupBy('producto_id')
            ->orderByDesc('unidades')
            ->with('producto')
            ->limit(10)
            ->get();

        return view('admin.reportes', [
            'fecha' => $fecha,
            'pagos' => $pagos,
            'consumo' => (float) $pagos->sum('consumo'),
            'propinas' => (float) $pagos->sum('propina'),
            'impuestos' => (float) $pagos->sum('impuesto'),
            'total' => (float) $pagos->sum('total'),
            'porMetodo' => $porMetodo,
            'masVendidos' => $masVendidos,
        ]);
    }
}
