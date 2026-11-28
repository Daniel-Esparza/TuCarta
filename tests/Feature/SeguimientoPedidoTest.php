<?php

namespace Tests\Feature;

use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Negocio;
use App\Models\Pedido;
use App\Models\PedidoEstadoHistorial;
use App\Models\PedidoItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SeguimientoPedidoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('seguimiento');
        $this->negocio = Negocio::factory()->create(['zona_horaria' => 'America/Mexico_City']);
    }

    private function url(Negocio $negocio, string $ruta = '/seguimiento'): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').$ruta;
    }

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

    private function agregarPaso(Pedido $pedido, EstadoPedido $estado, string $fechaLocal): void
    {
        $fecha = Carbon::parse($fechaLocal, 'America/Mexico_City')->utc();
        PedidoEstadoHistorial::withoutGlobalScopes()->insert([
            'pedido_id' => $pedido->id,
            'negocio_id' => $pedido->negocio_id,
            'estado_nuevo' => $estado->value,
            'created_at' => $fecha,
        ]);
    }

    public function test_un_folio_valido_muestra_el_estado_actual_y_los_pasos(): void
    {
        $pedido = $this->pedido($this->negocio, 'SEG101', EstadoPedido::EnPreparacion, '2026-11-27 12:00');
        $this->agregarPaso($pedido, EstadoPedido::EnPreparacion, '2026-11-27 12:15');

        $respuesta = $this->get($this->url($this->negocio, '/seguimiento/'.$pedido->folio));

        $respuesta->assertOk();
        $respuesta->assertSee('Pedido SEG101');
        $respuesta->assertSee('Estado actual:');
        $respuesta->assertSee(EstadoPedido::Recibido->value);
        $respuesta->assertSee(EstadoPedido::EnPreparacion->value);
        $respuesta->assertSeeInOrder([
            EstadoPedido::Recibido->value,
            EstadoPedido::EnPreparacion->value,
        ]);
    }

    public function test_la_consulta_funciona_sin_sesion(): void
    {
        $pedido = $this->pedido($this->negocio, 'SEG102', EstadoPedido::Recibido, '2026-11-27 12:00');

        $this->assertGuest();

        $this->get($this->url($this->negocio, '/seguimiento/'.$pedido->folio))
            ->assertOk();
    }

    public function test_la_respuesta_no_incluye_datos_personales(): void
    {
        $negocio = Negocio::factory()->create([
            'zona_horaria' => 'America/Mexico_City',
            'telefono_whatsapp' => '4441234567',
        ]);
        $pedido = $this->pedido($negocio, 'SEG103', EstadoPedido::Recibido, '2026-11-27 12:00', [['Taco al pastor', 1, '22.00']], [
            'cliente_nombre' => 'Zoila Mendoza Rangel',
            'direccion_entrega' => 'Calle Secreta 123',
        ]);

        $respuesta = $this->get($this->url($negocio, '/seguimiento/'.$pedido->folio));

        $respuesta->assertOk();
        $respuesta->assertDontSee('Zoila Mendoza Rangel');
        $respuesta->assertDontSee('Calle Secreta 123');
        $respuesta->assertDontSee('4441234567');
    }

    public function test_un_folio_inexistente_o_de_otro_negocio_muestra_el_mensaje_claro(): void
    {
        $otroNegocio = Negocio::factory()->create(['zona_horaria' => 'America/Mexico_City']);
        $pedidoOtro = $this->pedido($otroNegocio, 'OTRO99', EstadoPedido::Recibido, '2026-11-27 12:00');
        $this->agregarPaso($pedidoOtro, EstadoPedido::EnPreparacion, '2026-11-27 12:15');

        $this->get($this->url($this->negocio, '/seguimiento/ZZZZZZ'))
            ->assertNotFound()
            ->assertSee('ZZZZZZ');

        $this->get($this->url($this->negocio, '/seguimiento/'.$pedidoOtro->folio))
            ->assertNotFound()
            ->assertDontSee('Estado actual:')
            ->assertDontSee(EstadoPedido::EnPreparacion->value);

        $pedidoMismo = $this->pedido($this->negocio, 'MISMO1', EstadoPedido::Recibido, '2026-11-27 12:00');
        $pedidoOtroMismo = $this->pedido($otroNegocio, 'MISMO1', EstadoPedido::Recibido, '2026-11-27 12:00');
        $this->agregarPaso($pedidoOtroMismo, EstadoPedido::EnPreparacion, '2026-11-27 12:20');

        $this->get($this->url($this->negocio, '/seguimiento/'.$pedidoMismo->folio))
            ->assertOk()
            ->assertSee('Pedido MISMO1')
            ->assertSee(EstadoPedido::Recibido->value)
            ->assertDontSee(EstadoPedido::EnPreparacion->value);
    }

    public function test_al_rebasar_el_limite_de_consultas_aparece_el_aviso(): void
    {
        $url = $this->url($this->negocio, '/seguimiento/ZZZZZZ');
        $servidor = ['REMOTE_ADDR' => '10.9.9.9'];

        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables($servidor)->get($url)->assertNotFound();
        }

        $this->withServerVariables($servidor)->get($url)
            ->assertStatus(429);
    }
}
