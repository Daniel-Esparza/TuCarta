<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Usuario;
use App\Support\NegocioActual;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * US-10: métricas del negocio.
 * Los pedidos se crean fijando el negocio actual (igual que hace el
 * middleware del subdominio) para controlar estados, totales y fechas.
 */
class MetricasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
    }

    private function panel(Negocio $negocio, string $ruta): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/panel'.$ruta;
    }

    private function empleadoConPermisos(Negocio $negocio, array $claves): Usuario
    {
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        foreach ($claves as $clave) {
            $empleado->otorgarPermiso($clave, $negocio);
        }

        return $empleado;
    }

    /**
     * Crea un pedido con sus líneas fijando el negocio, como el middleware.
     *
     * @param  array<int, array{nombre: string, cantidad: int, precio?: float}>  $lineas
     */
    private function pedido(Negocio $negocio, EstadoPedido $estado, float $total, array $lineas = [], ?string $fecha = null): Pedido
    {
        app(NegocioActual::class)->establecer($negocio);

        $pedido = Pedido::create([
            'folio' => 'M-'.Str::upper(Str::random(10)),
            'cliente_nombre' => 'Cliente de prueba',
            'tipo_entrega' => TipoEntrega::EnSitio,
            'total' => $total,
            'estado' => $estado,
        ]);

        foreach ($lineas as $linea) {
            $precio = $linea['precio'] ?? 10.0;

            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'item_id' => null,
                'nombre_item' => $linea['nombre'],
                'precio_unitario' => $precio,
                'cantidad' => $linea['cantidad'],
                'subtotal' => $precio * $linea['cantidad'],
            ]);
        }

        if ($fecha !== null) {
            $pedido->forceFill(['created_at' => $fecha])->save();
        }

        return $pedido;
    }

    private function metricas(Negocio $negocio, string $extra = ''): TestResponse
    {
        // El middleware fija el negocio en cada petición; se fija también
        // aquí por si el proceso quedó con otro negocio tras crear datos.
        app(NegocioActual::class)->establecer($negocio);

        return $this->get($this->panel($negocio, '/metricas'.$extra));
    }

    public function test_total_estados_y_ticket_promedio_salen_correctos(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::VerMetricas]);

        $this->pedido($negocio, EstadoPedido::Recibido, 100);
        $this->pedido($negocio, EstadoPedido::Confirmado, 200);
        $this->pedido($negocio, EstadoPedido::EnPreparacion, 150);
        $this->pedido($negocio, EstadoPedido::Cancelado, 300);

        $this->actingAs($empleado);

        $respuesta = $this->metricas($negocio);

        $respuesta->assertOk();
        $respuesta->assertViewHas('totalPedidos', 4);
        $respuesta->assertViewHas('ventas', 450.0);
        $respuesta->assertViewHas('ticketPromedio', 150.0);

        $porEstado = $respuesta->viewData('porEstado');
        $this->assertSame(1, $porEstado['recibido']);
        $this->assertSame(1, $porEstado['confirmado']);
        $this->assertSame(1, $porEstado['en_preparacion']);
        $this->assertSame(1, $porEstado['cancelado']);
        $this->assertArrayNotHasKey('entregado', $porEstado->all());
    }

    public function test_los_diez_mas_pedidos_salen_en_orden_y_solo_diez(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::VerMetricas]);

        // 12 productos distintos: el i-ésimo vendido i veces.
        $lineas = [];
        for ($i = 1; $i <= 12; $i++) {
            $lineas[] = ['nombre' => 'Producto '.$i, 'cantidad' => $i, 'precio' => 10.0];
        }
        $this->pedido($negocio, EstadoPedido::Recibido, 780, $lineas);

        $this->actingAs($empleado);

        $top = $this->metricas($negocio)->viewData('topProductos');

        $this->assertCount(10, $top);
        $this->assertSame('Producto 12', $top[0]->nombre_item);
        $this->assertSame('Producto 11', $top[1]->nombre_item);
        $this->assertSame('Producto 3', $top[9]->nombre_item);
        $this->assertSame(12, (int) $top[0]->cantidad_total);
    }

    public function test_un_pedido_de_otro_negocio_no_cambia_las_metricas(): void
    {
        $negocioA = Negocio::factory()->create();
        $negocioB = Negocio::factory()->create();
        $empleadoA = $this->empleadoConPermisos($negocioA, [ClavePermiso::VerMetricas]);

        $this->pedido($negocioA, EstadoPedido::Recibido, 100, [
            ['nombre' => 'Pizza de A', 'cantidad' => 2, 'precio' => 50.0],
        ]);
        $this->pedido($negocioB, EstadoPedido::Recibido, 999, [
            ['nombre' => 'Pizza de B', 'cantidad' => 9, 'precio' => 111.0],
        ]);

        $this->actingAs($empleadoA);

        $respuesta = $this->metricas($negocioA);

        $respuesta->assertOk();
        $respuesta->assertViewHas('totalPedidos', 1);
        $respuesta->assertViewHas('ventas', 100.0);

        $top = $respuesta->viewData('topProductos');
        $this->assertCount(1, $top);
        $this->assertSame('Pizza de A', $top[0]->nombre_item);
        $this->assertSame(2, (int) $top[0]->cantidad_total);
    }

    public function test_sin_pedidos_en_el_periodo_aparece_el_aviso(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::VerMetricas]);

        // Hay un pedido hoy, pero se consulta un mes sin pedidos.
        $this->pedido($negocio, EstadoPedido::Recibido, 100);

        $this->actingAs($empleado);

        $respuesta = $this->metricas($negocio, '?desde=2020-01-01&hasta=2020-01-31');

        $respuesta->assertOk();
        $respuesta->assertViewHas('hayPedidos', false);
        $respuesta->assertSee('No hay pedidos entre el 2020-01-01 y el 2020-01-31');
    }

    public function test_el_filtro_trae_por_defecto_el_mes_actual(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::VerMetricas]);

        $mesPasado = now()->startOfMonth()->subMonth()->startOfMonth();

        $this->pedido($negocio, EstadoPedido::Recibido, 100, [], now()->toDateTimeString());
        $this->pedido($negocio, EstadoPedido::Recibido, 250, [], $mesPasado->copy()->addDays(2)->toDateTimeString());

        $this->actingAs($empleado);

        // Sin fechas: solo el mes en curso.
        $respuesta = $this->metricas($negocio);
        $respuesta->assertOk();
        $respuesta->assertViewHas('totalPedidos', 1);
        $respuesta->assertViewHas('ventas', 100.0);

        // Con el rango del mes pasado: solo aquel pedido.
        $respuesta = $this->metricas($negocio,
            '?desde='.$mesPasado->toDateString().'&hasta='.$mesPasado->copy()->endOfMonth()->toDateString());
        $respuesta->assertOk();
        $respuesta->assertViewHas('totalPedidos', 1);
        $respuesta->assertViewHas('ventas', 250.0);
    }

    public function test_un_empleado_sin_permiso_no_ve_las_metricas(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado);

        $this->metricas($negocio)->assertForbidden();
    }

    public function test_las_fechas_invertidas_marcan_error(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::VerMetricas]);

        $this->actingAs($empleado);

        $this->metricas($negocio, '?desde=2026-02-10&hasta=2026-01-01')
            ->assertSessionHasErrors('desde');
    }
}
