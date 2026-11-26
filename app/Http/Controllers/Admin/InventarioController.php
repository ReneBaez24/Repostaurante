<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OpenFoodFacts\CodigoBarrasInvalido;
use App\Exceptions\OpenFoodFacts\OpenFoodFactsException;
use App\Exceptions\OpenFoodFacts\ProductoNoEncontrado;
use App\Http\Controllers\Controller;
use App\Models\Ingrediente;
use App\Services\OpenFoodFacts\OpenFoodFactsClient;
use App\Support\CodigoBarras;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function __construct(private OpenFoodFactsClient $openFoodFacts) {}

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('q'));

        $ingredientes = Ingrediente::query()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")
                ->orWhere('codigo_barras', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventario.index', ['ingredientes' => $ingredientes, 'buscar' => $buscar]);
    }

    /** Paso 1: consultar un código en Open Food Facts y mostrar la vista previa. */
    public function consultar(Request $request): View|RedirectResponse
    {
        $request->validate(
            ['codigo' => ['required', 'string', 'max:30']],
            ['codigo.required' => 'Escribe o escanea un código de barras.', 'codigo.max' => 'El código es demasiado largo; un código de barras tiene como máximo 14 dígitos.'],
        );

        $entrada = (string) $request->query('codigo');
        $existente = null;

        try {
            $codigo = CodigoBarras::validar($entrada);
            $existente = Ingrediente::firstWhere('codigo_barras', $codigo);
            $producto = $this->openFoodFacts->buscar($codigo);
        } catch (CodigoBarrasInvalido $e) {
            return redirect()->route('admin.inventario.index')->withInput(['codigo' => $entrada])->with('error', $e->getMessage());
        } catch (ProductoNoEncontrado $e) {
            // No está en la base pública: se ofrece el registro manual
            return view('admin.inventario.consultar', ['codigo' => $codigo, 'producto' => null, 'aviso' => $e->getMessage(), 'existente' => $existente]);
        } catch (OpenFoodFactsException $e) {
            return redirect()->route('admin.inventario.index')->withInput(['codigo' => $entrada])->with('error', $e->getMessage());
        }

        return view('admin.inventario.consultar', ['codigo' => $codigo, 'producto' => $producto, 'aviso' => null, 'existente' => $existente]);
    }

    /** Paso 2: guardar en el inventario (con los datos de la API o capturados a mano). */
    public function store(Request $request): RedirectResponse
    {
        $manual = $request->boolean('manual');

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'existencias' => ['required', 'numeric', 'min:0', 'max:999999'],
            'unidad' => ['required', Rule::in(Ingrediente::UNIDADES)],
            'nombre' => [Rule::requiredIf($manual), 'nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:100'],
            'presentacion' => ['nullable', 'string', 'max:60'],
        ], [
            'existencias.required' => 'Indica cuántas unidades hay en existencia.',
            'existencias.numeric' => 'Las existencias deben ser un número (por ejemplo 12 o 2.5).',
            'existencias.min' => 'Las existencias no pueden ser negativas.',
            'unidad.in' => 'Elige una unidad de la lista.',
            'nombre.required' => 'Escribe el nombre del ingrediente.',
        ]);

        try {
            $codigo = CodigoBarras::validar($datos['codigo']);

            // Los datos del producto se vuelven a pedir (desde la caché) en vez de confiar en el formulario
            $columnas = $manual
                ? ['codigo_barras' => $codigo, 'nombre' => $datos['nombre'], 'marca' => $datos['marca'] ?? null,
                    'presentacion' => $datos['presentacion'] ?? null, 'origen' => 'manual']
                : Ingrediente::datosDesde($this->openFoodFacts->buscar($codigo));
        } catch (OpenFoodFactsException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ingrediente = Ingrediente::updateOrCreate(
            ['codigo_barras' => $codigo],
            $columnas + ['existencias' => $datos['existencias'], 'unidad' => $datos['unidad']],
        );

        return redirect()->route('admin.inventario.index')
            ->with('ok', "«{$ingrediente->nombre}» guardado en el inventario.");
    }

    public function update(Request $request, Ingrediente $ingrediente): RedirectResponse
    {
        $datos = $request->validate([
            'existencias' => ['required', 'numeric', 'min:0', 'max:999999'],
            'unidad' => ['required', Rule::in(Ingrediente::UNIDADES)],
        ], [
            'existencias.numeric' => 'Las existencias deben ser un número.',
            'existencias.min' => 'Las existencias no pueden ser negativas.',
        ]);

        $ingrediente->update($datos);

        return back()->with('ok', "Existencias de «{$ingrediente->nombre}» actualizadas.");
    }

    public function destroy(Ingrediente $ingrediente): RedirectResponse
    {
        $ingrediente->delete();

        return back()->with('ok', "«{$ingrediente->nombre}» eliminado del inventario.");
    }

    /**
     * Endpoint JSON: GET /admin/inventario/api/{codigo}
     * 200 {data: {...}} · 422 código inválido · 404 no existe · 429/503 problema con la API
     */
    public function api(string $codigo): JsonResponse
    {
        try {
            $producto = $this->openFoodFacts->buscar($codigo);
        } catch (OpenFoodFactsException $e) {
            return response()->json([
                'error' => ['tipo' => $e->tipo(), 'mensaje' => $e->getMessage()],
            ], $e->status());
        }

        return response()->json([
            'data' => $producto->toArray() + [
                'en_inventario' => Ingrediente::where('codigo_barras', $producto->codigo)->exists(),
            ],
        ]);
    }
}
