<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\CocinaController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

/*
| Autenticación
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
| Flujo del restaurante (cualquier usuario con sesión: admin o usuario)
| Mesas → Pedidos → Cocina → Cobro
*/
Route::middleware('auth')->group(function () {
    // 1. Vista general (mesas)
    Route::get('/', [MesaController::class, 'index'])->name('overview');
    Route::post('/mesas/{mesa}/abrir', [PedidoController::class, 'abrir'])->name('mesas.abrir');
    Route::post('/mesas/{mesa}/liberar', [MesaController::class, 'liberar'])->name('mesas.liberar');

    // 2. Selección de pedidos
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('order');
    Route::post('/pedidos/{pedido}/productos/{producto}/agregar', [PedidoController::class, 'agregar'])->name('pedidos.agregar');
    Route::post('/pedidos/{pedido}/productos/{producto}/quitar', [PedidoController::class, 'quitar'])->name('pedidos.quitar');
    Route::patch('/pedidos/items/{item}/notas', [PedidoController::class, 'notas'])->name('pedidos.notas');
    Route::post('/pedidos/{pedido}/enviar-cocina', [PedidoController::class, 'enviarCocina'])->name('pedidos.enviar');
    Route::post('/pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');

    // Cocina
    Route::get('/cocina', [CocinaController::class, 'index'])->name('cocina');
    Route::patch('/cocina/items/{item}', [CocinaController::class, 'preparado'])->name('cocina.preparado');
    Route::patch('/cocina/pedidos/{pedido}', [CocinaController::class, 'pedidoPreparado'])->name('cocina.pedido');

    // 3. Sección de cobro
    Route::get('/pedidos/{pedido}/cobro', [CobroController::class, 'show'])->name('checkout');
    Route::post('/pedidos/{pedido}/cobro', [CobroController::class, 'store'])->name('checkout.store');
});

/*
| Administración (sólo rol admin)
*/
Route::middleware(['auth', 'rol:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reportes', [Admin\DashboardController::class, 'reportes'])->name('reportes');

    Route::resource('usuarios', Admin\UsuarioController::class)->except('show')->parameters(['usuarios' => 'usuario']);
    Route::resource('productos', Admin\ProductoController::class)->except('show')->parameters(['productos' => 'producto']);
    Route::resource('mesas', Admin\MesaController::class)->except('show')->parameters(['mesas' => 'mesa']);

    // Inventario de ingredientes envasados (Open Food Facts)
    Route::get('/inventario', [Admin\InventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/consultar', [Admin\InventarioController::class, 'consultar'])->name('inventario.consultar');
    Route::get('/inventario/api/{codigo}', [Admin\InventarioController::class, 'api'])->name('inventario.api');
    Route::post('/inventario', [Admin\InventarioController::class, 'store'])->name('inventario.store');
    Route::patch('/inventario/{ingrediente}', [Admin\InventarioController::class, 'update'])->name('inventario.update');
    Route::delete('/inventario/{ingrediente}', [Admin\InventarioController::class, 'destroy'])->name('inventario.destroy');
});
