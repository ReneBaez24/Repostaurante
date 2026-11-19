<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(): View
    {
        return view('admin.productos.index', [
            'productos' => Producto::with('categoria')->orderBy('categoria_id')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.productos.form', [
            'producto' => new Producto(['disponible' => true]),
            'categorias' => Categoria::orderBy('orden')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Producto::create($this->validar($request));

        return redirect()->route('admin.productos.index')->with('ok', 'Producto agregado al menú.');
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('orden')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validar($request));

        return redirect()->route('admin.productos.index')->with('ok', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->pedidoItems()->exists()) {
            // Ya aparece en cuentas: sólo se oculta del menú para no romper el historial
            $producto->update(['disponible' => false]);

            return back()->with('ok', 'El producto ya tiene ventas, así que se marcó como no disponible.');
        }

        $producto->delete();

        return back()->with('ok', 'Producto eliminado.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);
        $datos['disponible'] = $request->boolean('disponible');

        return $datos;
    }
}
