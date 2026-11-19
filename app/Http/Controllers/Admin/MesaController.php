<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ZonaMesa;
use App\Http\Controllers\Controller;
use App\Models\Mesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MesaController extends Controller
{
    public function index(): View
    {
        return view('admin.mesas.index', ['mesas' => Mesa::with('pedidoActual.items')->orderBy('numero')->get()]);
    }

    public function create(): View
    {
        $siguiente = (int) Mesa::max('numero') + 1;

        return view('admin.mesas.form', [
            'mesa' => new Mesa(['numero' => $siguiente, 'capacidad' => 4, 'zona' => ZonaMesa::Interior, 'activa' => true]),
            'zonas' => ZonaMesa::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Mesa::create($this->validar($request));

        return redirect()->route('admin.mesas.index')->with('ok', 'Mesa creada.');
    }

    public function edit(Mesa $mesa): View
    {
        return view('admin.mesas.form', ['mesa' => $mesa, 'zonas' => ZonaMesa::cases()]);
    }

    public function update(Request $request, Mesa $mesa): RedirectResponse
    {
        $datos = $this->validar($request, $mesa);

        if (! $datos['activa'] && $mesa->pedidoActual()->exists()) {
            return back()->withInput()->with('error', 'No puedes desactivar una mesa ocupada. Cóbrala y libérala primero.');
        }

        $mesa->update($datos);

        return redirect()->route('admin.mesas.index')->with('ok', 'Mesa actualizada.');
    }

    public function destroy(Mesa $mesa): RedirectResponse
    {
        if ($mesa->pedidos()->exists()) {
            if ($mesa->pedidoActual()->exists()) {
                return back()->with('error', 'La mesa está ocupada. Cóbrala y libérala primero.');
            }
            $mesa->update(['activa' => false]);

            return back()->with('ok', 'La mesa tiene historial, así que se desactivó en lugar de eliminarse.');
        }

        $mesa->delete();

        return back()->with('ok', 'Mesa eliminada.');
    }

    private function validar(Request $request, ?Mesa $mesa = null): array
    {
        $datos = $request->validate([
            'numero' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('mesas', 'numero')->ignore($mesa?->id)],
            'capacidad' => ['required', 'integer', 'min:1', 'max:30'],
            'zona' => ['required', Rule::enum(ZonaMesa::class)],
        ]);
        $datos['activa'] = $request->boolean('activa');

        return $datos;
    }
}
