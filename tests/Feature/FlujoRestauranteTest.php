<?php

namespace Tests\Feature;

use App\Enums\EstadoItem;
use App\Enums\EstadoMesa;
use App\Enums\EstadoPedido;
use App\Models\Mesa;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlujoRestauranteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_flujo_completo_mesa_pedido_cocina_cobro(): void
    {
        $mesero = User::where('email', 'rene@punkbrothers.com')->first();
        $mesa = Mesa::where('numero', 8)->first();
        $pollo = Producto::where('nombre', '¼ pollo ahumado')->first();     // 12.00
        $asado = Producto::where('nombre', '¼ pollo asado')->first();       // 9.50
        $limonada = Producto::where('nombre', 'Limonada casera')->first();  // 3.50
        $papas = Producto::where('nombre', 'Papas rústicas')->first();      // 6.00

        $this->actingAs($mesero);

        // 1. Vista general
        $this->get('/?mesa='.$mesa->id)->assertOk()->assertSee('MESA 08')->assertSee('Libres · 12');

        // Abrir mesa
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 2, 'zona' => 'interior'])
            ->assertRedirect();
        $pedido = Pedido::first();
        $this->assertSame(EstadoMesa::Abierta, $mesa->fresh()->estado());

        // 2. Agregar productos (igual que la pantalla: total $46.50)
        foreach ([$pollo, $pollo, $asado, $limonada, $limonada, $papas] as $p) {
            $this->post(route('pedidos.agregar', [$pedido, $p]))->assertRedirect();
        }
        // + y − : agrega uno de más y lo quita
        $this->post(route('pedidos.agregar', [$pedido, $papas]));
        $this->post(route('pedidos.quitar', [$pedido, $papas]));

        $pedido->refresh()->load('items');
        $this->assertSame(46.50, $pedido->total());
        $this->assertSame(6, $pedido->unidades());
        $this->get(route('order', $pedido))->assertOk()->assertSee('$46.50')->assertSee('Enviar a cocina • 6 nuevos');

        // No se puede cobrar con productos sin enviar
        $this->post(route('checkout.store', $pedido), ['metodo' => 'tarjeta', 'propina' => 10])
            ->assertSessionHas('error');
        $this->assertSame(0, Pago::count());

        // Enviar a cocina
        $this->post(route('pedidos.enviar', $pedido))->assertSessionHas('ok');
        $this->assertSame(EstadoMesa::EsperandoOrden, $mesa->fresh()->estado());
        $this->assertFalse($pedido->items()->where('estado', EstadoItem::SinEnviar->value)->exists());

        // Lo enviado ya no se puede quitar
        $this->post(route('pedidos.quitar', [$pedido, $pollo]))->assertSessionHas('error');

        // Cocina
        $this->get(route('cocina'))->assertOk()->assertSee('MESA 08');
        $this->patch(route('cocina.pedido', $pedido))->assertSessionHas('ok');
        $this->assertSame(EstadoMesa::Ocupada, $mesa->fresh()->estado());

        // 3. Cobro
        $this->get(route('checkout', $pedido))->assertOk()->assertSee('Todo preparado')->assertSee('$4.23');

        $this->post(route('checkout.store', $pedido), [
            'metodo' => 'tarjeta', 'propina' => 10,
        ])->assertRedirect(route('overview', ['mesa' => $mesa->id]));

        $pago = Pago::first();
        $this->assertEquals('46.50', $pago->consumo);
        $this->assertEquals('4.65', $pago->propina);
        $this->assertEquals('51.15', $pago->total);
        $this->assertEquals('4.23', $pago->impuesto);
        $this->assertSame(EstadoPedido::Pagado, $pedido->fresh()->estado);

        // Después de cobrar la mesa sigue ocupada y no se puede volver a abrir
        $this->assertSame(EstadoMesa::Ocupada, $mesa->fresh()->estado());
        $this->get('/?mesa='.$mesa->id)->assertOk()->assertSee('Cuenta pagada')->assertSee('Liberar mesa');
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 2, 'zona' => 'interior'])
            ->assertSessionHas('error');
        $this->assertSame(1, Pedido::count());

        // Liberar mesa desde la vista general
        $this->post(route('mesas.liberar', $mesa))->assertSessionHas('ok');
        $this->assertSame(EstadoMesa::Libre, $mesa->fresh()->estado());
        $this->assertNotNull($pedido->fresh()->liberada_at);

        // No se puede cobrar dos veces
        $this->post(route('checkout.store', $pedido), ['metodo' => 'efectivo', 'propina' => 0])
            ->assertSessionHas('error');
        $this->assertSame(1, Pago::count());

        // El reporte del admin lo refleja
        $admin = User::where('email', 'admin@punkbrothers.com')->first();
        $this->actingAs($admin)->get(route('admin.reportes'))->assertOk()->assertSee('$51.15');
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('$51.15');
    }

    public function test_no_se_puede_abrir_mesa_con_mas_comensales_que_sillas(): void
    {
        $mesa = Mesa::where('numero', 2)->first(); // 2 sillas

        $this->actingAs(User::first())
            ->post(route('mesas.abrir', $mesa), ['comensales' => 5, 'zona' => 'interior'])
            ->assertSessionHasErrors('comensales');

        $this->assertSame(0, Pedido::count());
    }

    public function test_abrir_dos_veces_la_misma_mesa_reutiliza_el_pedido(): void
    {
        $mesa = Mesa::first();
        $this->actingAs(User::first());

        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);

        $this->assertSame(1, Pedido::count());
    }

    public function test_no_se_puede_liberar_una_mesa_sin_cobrar(): void
    {
        $mesa = Mesa::first();
        $this->actingAs(User::first());
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);

        $this->post(route('mesas.liberar', $mesa))->assertSessionHas('error');
        $this->assertSame(EstadoMesa::Abierta, $mesa->fresh()->estado());
    }

    public function test_cocina_ve_lo_pendiente_aunque_ya_se_haya_cobrado(): void
    {
        $mesa = Mesa::first();
        $this->actingAs(User::first());
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);
        $pedido = Pedido::first();
        $this->post(route('pedidos.agregar', [$pedido, Producto::first()]));
        $this->post(route('pedidos.enviar', $pedido));
        $this->post(route('checkout.store', $pedido), ['metodo' => 'efectivo', 'propina' => 0]);

        $this->assertSame(EstadoMesa::EsperandoOrden, $mesa->fresh()->estado());
        $this->get(route('cocina'))->assertSee('MESA 01');

        $this->patch(route('cocina.pedido', $pedido));
        $this->assertSame(EstadoMesa::Ocupada, $mesa->fresh()->estado());
    }

    public function test_cancelar_pedido_deja_la_mesa_libre(): void
    {
        $mesa = Mesa::first();
        $this->actingAs(User::first());
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);
        $pedido = Pedido::first();

        $this->get(route('order', $pedido))->assertSee('Cancelar pedido');
        $this->post(route('pedidos.cancelar', $pedido))->assertRedirect(route('overview', ['mesa' => $mesa->id]));

        $this->assertSame(EstadoPedido::Cancelado, $pedido->fresh()->estado);
        $this->assertSame(EstadoMesa::Libre, $mesa->fresh()->estado());
    }

    public function test_el_precio_queda_fijo_aunque_cambie_el_menu(): void
    {
        $mesa = Mesa::first();
        $producto = Producto::first();
        $this->actingAs(User::first());
        $this->post(route('mesas.abrir', $mesa), ['comensales' => 1, 'zona' => 'interior']);
        $pedido = Pedido::first();
        $this->post(route('pedidos.agregar', [$pedido, $producto]));
        $precioOriginal = (float) $producto->precio;

        $producto->update(['precio' => 999]);

        $this->assertSame($precioOriginal, $pedido->fresh()->load('items')->total());
    }

    public function test_todas_las_pantallas_cargan(): void
    {
        $admin = User::where('email', 'admin@punkbrothers.com')->first();
        $this->actingAs($admin);

        foreach ([
            'admin.dashboard', 'admin.reportes', 'admin.usuarios.index', 'admin.usuarios.create',
            'admin.productos.index', 'admin.productos.create', 'admin.mesas.index', 'admin.mesas.create', 'cocina',
        ] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
        $this->get(route('admin.usuarios.edit', $admin))->assertOk();
        $this->get(route('admin.productos.edit', Producto::first()))->assertOk();
        $this->get(route('admin.mesas.edit', Mesa::first()))->assertOk();
        $this->get('/?filtro=libres')->assertOk();
    }
}
