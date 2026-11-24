<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\PedidoEstadoHistorial;
use App\Models\PedidoItem;
use App\Models\PedidoItemOpcion;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PedidosPanelTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private Usuario $dueno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
        $this->negocio = Negocio::factory()->create(['zona_horaria' => 'America/Mexico_City']);
        $this->dueno = Usuario::factory()->deNegocio($this->negocio)->create();
        $this->dueno->otorgarPermisosDeDueno();
    }

    private function url(Negocio $negocio, string $ruta = '/panel/pedidos'): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').$ruta;
    }

    // La fecha va en la hora local del negocio (Ciudad de México).
    private function pedido(Negocio $negocio, string $folio, EstadoPedido $estado, string $fechaLocal, array $lineas = [['Taco al pastor', 2, '22.00']], array $extra = []): Pedido
    {
        $fecha = Carbon::parse($fechaLocal, 'America/Mexico_City')->utc();

        $pedido = new Pedido($extra + [
            'negocio_id' => $negocio->id,
            'folio' => $folio,
            'cliente_nombre' => 'Cliente de prueba',
            'tipo_entrega' => TipoEntrega::EnSitio,
            'total' => '0.00',
            'estado' => $estado,
        ]);
        $pedido->timestamps = false;
        $pedido->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

        $total = 0;
        foreach ($lineas as [$nombre, $cantidad, $precio]) {
            $subtotal = $cantidad * (float) $precio;
            $total += $subtotal;
            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'negocio_id' => $negocio->id,
                'nombre_item' => $nombre,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => number_format($subtotal, 2, '.', ''),
            ]);
        }
        $pedido->update(['total' => number_format($total, 2, '.', '')]);

        PedidoEstadoHistorial::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $negocio->id,
            'estado_nuevo' => EstadoPedido::Recibido,
        ]);

        return $pedido;
    }

    public function test_la_lista_muestra_folio_fecha_productos_cantidades_y_total(): void
    {
        $this->pedido($this->negocio, 'ABC234', EstadoPedido::Recibido, '2026-11-20 13:45', [
            ['Taco al pastor', 3, '22.00'],
            ['Agua de horchata', 1, '30.00'],
        ]);

        $this->actingAs($this->dueno)->get($this->url($this->negocio))
            ->assertOk()
            ->assertSee('ABC234')
            ->assertSee('20/11/2026 13:45')
            ->assertSee('3 × Taco al pastor')
            ->assertSee('1 × Agua de horchata')
            ->assertSee('$96.00')
            ->assertSee('Recibido');
    }

    public function test_los_mas_recientes_salen_primero(): void
    {
        $this->pedido($this->negocio, 'VIEJO2', EstadoPedido::Entregado, '2026-11-01 10:00');
        $this->pedido($this->negocio, 'NUEVO3', EstadoPedido::Recibido, '2026-11-20 10:00');

        $this->actingAs($this->dueno)->get($this->url($this->negocio))
            ->assertSeeInOrder(['NUEVO3', 'VIEJO2']);
    }

    public function test_filtrar_por_estado_devuelve_solo_ese_estado(): void
    {
        $this->pedido($this->negocio, 'LISTO2', EstadoPedido::Listo, '2026-11-20 10:00');
        $this->pedido($this->negocio, 'CANCE3', EstadoPedido::Cancelado, '2026-11-20 11:00');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?estado=listo'))
            ->assertOk()
            ->assertSee('LISTO2')
            ->assertDontSee('CANCE3');
    }

    public function test_filtrar_por_fechas_incluye_el_dia_inicial_y_el_final_completos(): void
    {
        $this->pedido($this->negocio, 'ANTES2', EstadoPedido::Entregado, '2026-11-09 23:59');
        $this->pedido($this->negocio, 'INICI3', EstadoPedido::Entregado, '2026-11-10 00:00');
        $this->pedido($this->negocio, 'FINAL4', EstadoPedido::Entregado, '2026-11-12 23:59');
        $this->pedido($this->negocio, 'DESPU5', EstadoPedido::Entregado, '2026-11-13 00:01');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?desde=2026-11-10&hasta=2026-11-12'))
            ->assertOk()
            ->assertSee('INICI3')
            ->assertSee('FINAL4')
            ->assertDontSee('ANTES2')
            ->assertDontSee('DESPU5');
    }

    public function test_los_filtros_se_combinan(): void
    {
        $this->pedido($this->negocio, 'SIRVE2', EstadoPedido::Listo, '2026-11-11 12:00');
        $this->pedido($this->negocio, 'OTROE3', EstadoPedido::Entregado, '2026-11-11 12:00');
        $this->pedido($this->negocio, 'OTRAF4', EstadoPedido::Listo, '2026-11-15 12:00');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?estado=listo&desde=2026-11-10&hasta=2026-11-12'))
            ->assertSee('SIRVE2')
            ->assertDontSee('OTROE3')
            ->assertDontSee('OTRAF4');
    }

    public function test_los_filtros_se_conservan_al_paginar(): void
    {
        foreach (range(1, 21) as $n) {
            $this->pedido($this->negocio, sprintf('PAG%03d', $n), EstadoPedido::Listo, '2026-11-11 12:'.sprintf('%02d', $n));
        }

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?estado=listo'))
            ->assertSee('estado=listo&amp;page=2', false);
    }

    public function test_una_fecha_final_anterior_a_la_inicial_da_error(): void
    {
        $this->actingAs($this->dueno)
            ->from($this->url($this->negocio))
            ->get($this->url($this->negocio, '/panel/pedidos?desde=2026-11-12&hasta=2026-11-10'))
            ->assertSessionHasErrors('hasta');
    }

    public function test_buscar_por_folio(): void
    {
        $this->pedido($this->negocio, 'BUSCA2', EstadoPedido::Recibido, '2026-11-11 12:00');
        $this->pedido($this->negocio, 'NOESTE', EstadoPedido::Recibido, '2026-11-11 12:00');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?folio=busca2'))
            ->assertSee('BUSCA2')
            ->assertDontSee('NOESTE');
    }

    public function test_el_detalle_de_un_pedido_a_domicilio_muestra_todo(): void
    {
        $pedido = $this->pedido($this->negocio, 'DOMIC2', EstadoPedido::Recibido, '2026-11-20 13:10', [['Taco al pastor', 3, '32.00']], [
            'cliente_nombre' => 'Lucía Hernández',
            'tipo_entrega' => TipoEntrega::Domicilio,
            'direccion_entrega' => 'Av. Himno Nacional 230',
            'referencias_entrega' => 'Portón negro',
            'notas' => 'Sin cebolla',
        ]);
        PedidoItemOpcion::create([
            'pedido_item_id' => $pedido->items()->first()->id,
            'negocio_id' => $this->negocio->id,
            'nombre_grupo' => 'Extras',
            'nombre_opcion' => 'Queso',
            'incremento_precio' => '10.00',
        ]);

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos/'.$pedido->id))
            ->assertOk()
            ->assertSee('Pedido DOMIC2')
            ->assertSee('Lucía Hernández')
            ->assertSee('A domicilio')
            ->assertSee('Av. Himno Nacional 230')
            ->assertSee('Portón negro')
            ->assertSee('Sin cebolla')
            ->assertSee('Extras: Queso')
            ->assertSee('$96.00')
            ->assertSee('Historial del estado');
    }

    public function test_un_dueno_no_ve_los_pedidos_de_otro_negocio(): void
    {
        $otro = Negocio::factory()->create();
        $ajeno = $this->pedido($otro, 'AJENO2', EstadoPedido::Recibido, '2026-11-20 10:00');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos?negocio_id='.$otro->id))
            ->assertOk()
            ->assertDontSee('AJENO2');

        $this->actingAs($this->dueno)->get($this->url($this->negocio, '/panel/pedidos/'.$ajeno->id))->assertNotFound();
        $this->actingAs($this->dueno)->get($this->url($otro, '/panel/pedidos'))->assertNotFound();
    }

    public function test_el_empleado_con_permiso_ve_la_misma_bandeja(): void
    {
        $this->pedido($this->negocio, 'EMPLE2', EstadoPedido::Recibido, '2026-11-20 10:00');
        $empleado = Usuario::factory()->deNegocio($this->negocio)->create();
        $empleado->otorgarPermiso(ClavePermiso::VerPedidos, $this->negocio);

        $this->actingAs($empleado)->get($this->url($this->negocio))->assertOk()->assertSee('EMPLE2');
    }

    public function test_un_empleado_sin_permiso_recibe_403(): void
    {
        $empleado = Usuario::factory()->deNegocio($this->negocio)->create();

        $this->actingAs($empleado)->get($this->url($this->negocio))->assertForbidden();
    }

    public function test_sin_pedidos_se_muestra_un_aviso(): void
    {
        $this->actingAs($this->dueno)->get($this->url($this->negocio))
            ->assertOk()
            ->assertSee('Todavía no hay pedidos.');
    }
}
