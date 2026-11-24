<?php

namespace Tests\Feature;

use App\Models\GrupoOpcion;
use App\Models\Horario;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Opcion;
use App\Models\Pedido;
use App\Services\WhatsappPedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// US-20: tipo de pedido y datos de entrega.
class TipoPedidoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $pizzeria;
    private Item $margarita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->pizzeria = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();
        $this->margarita = Item::withoutGlobalScopes()->where('nombre', 'Pizza Margarita')->firstOrFail();

        // Abierto todos los días, para que la prueba no dependa de la hora.
        Horario::withoutGlobalScopes()->where('negocio_id', $this->pizzeria->id)->delete();
        foreach (range(1, 7) as $dia) {
            Horario::create([
                'negocio_id' => $this->pizzeria->id,
                'dia_semana' => $dia,
                'hora_apertura' => '00:00:00',
                'hora_cierre' => '23:59:59',
            ]);
        }
    }

    private function url(): string
    {
        return 'http://pizza-nova.'.config('tucarta.dominio').'/pedido';
    }

    private function carrito(): string
    {
        $opciones = GrupoOpcion::withoutGlobalScopes()
            ->where('item_id', $this->margarita->id)
            ->where('es_obligatorio', true)
            ->get()
            ->map(fn ($grupo) => Opcion::withoutGlobalScopes()
                ->where('grupo_opcion_id', $grupo->id)
                ->orderBy('orden')
                ->first())
            ->filter()
            ->map(fn ($opcion) => $opcion->id)
            ->values()
            ->all();

        return json_encode([[
            'item_id' => $this->margarita->id,
            'cantidad' => 1,
            'opciones' => $opciones,
        ]]);
    }

    private function enviar(array $extra = [])
    {
        return $this->post($this->url(), array_merge([
            'cliente_nombre' => 'Ana',
            'carrito' => $this->carrito(),
        ], $extra));
    }

    private function pedido(): Pedido
    {
        return Pedido::withoutGlobalScopes()->where('negocio_id', $this->pizzeria->id)->firstOrFail();
    }

    private function mensaje(Pedido $pedido): string
    {
        return app(WhatsappPedidoService::class)->construirMensaje($pedido, $this->pizzeria);
    }

    public function test_consumo_en_el_lugar_se_guarda(): void
    {
        $this->enviar(['tipo_entrega' => 'en_sitio'])->assertRedirect()->assertSessionHasNoErrors();

        $pedido = $this->pedido();
        $this->assertSame('en_sitio', $pedido->tipo_entrega->value);
        $this->assertNull($pedido->direccion_entrega);
    }

    public function test_para_llevar_se_guarda(): void
    {
        $this->enviar(['tipo_entrega' => 'para_llevar'])->assertRedirect()->assertSessionHasNoErrors();

        $pedido = $this->pedido();
        $this->assertSame('para_llevar', $pedido->tipo_entrega->value);
        $this->assertNull($pedido->direccion_entrega);
    }

    public function test_domicilio_se_guarda_con_direccion_y_referencias(): void
    {
        $this->enviar([
            'tipo_entrega' => 'domicilio',
            'direccion_entrega' => 'Calle Hidalgo 123',
            'referencias_entrega' => 'Portón negro',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pedido = $this->pedido();
        $this->assertSame('domicilio', $pedido->tipo_entrega->value);
        $this->assertSame('Calle Hidalgo 123', $pedido->direccion_entrega);
        $this->assertSame('Portón negro', $pedido->referencias_entrega);
    }

    public function test_domicilio_sin_direccion_es_rechazado_por_el_servidor(): void
    {
        $this->enviar(['tipo_entrega' => 'domicilio', 'direccion_entrega' => ''])
            ->assertSessionHasErrors('direccion_entrega');

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_para_llevar_ignora_una_direccion_enviada_a_mano(): void
    {
        $this->enviar([
            'tipo_entrega' => 'para_llevar',
            'direccion_entrega' => 'Calle Falsa 999',
            'referencias_entrega' => 'No debe guardarse',
        ])->assertRedirect();

        $pedido = $this->pedido();
        $this->assertNull($pedido->direccion_entrega);
        $this->assertNull($pedido->referencias_entrega);
    }

    public function test_un_tipo_de_pedido_inventado_es_rechazado(): void
    {
        $this->enviar(['tipo_entrega' => 'tipo_inventado'])
            ->assertSessionHasErrors('tipo_entrega');

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_sin_tipo_de_pedido_es_rechazado(): void
    {
        $this->enviar()->assertSessionHasErrors('tipo_entrega');

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_el_mensaje_de_whatsapp_cambia_segun_el_tipo(): void
    {
        $this->enviar(['tipo_entrega' => 'para_llevar']);
        $mensaje = $this->mensaje($this->pedido());

        $this->assertStringContainsString('Tipo de entrega: Para llevar', $mensaje);
        $this->assertStringNotContainsString('Dirección:', $mensaje);
    }

    public function test_el_mensaje_de_domicilio_incluye_direccion_y_referencias(): void
    {
        $this->enviar([
            'tipo_entrega' => 'domicilio',
            'direccion_entrega' => 'Calle Hidalgo 123',
            'referencias_entrega' => 'Portón negro',
        ]);
        $mensaje = $this->mensaje($this->pedido());

        $this->assertStringContainsString('Tipo de entrega: A domicilio', $mensaje);
        $this->assertStringContainsString('Dirección: Calle Hidalgo 123', $mensaje);
        $this->assertStringContainsString('Referencias: Portón negro', $mensaje);
    }

    public function test_el_total_no_incluye_costo_de_envio(): void
    {
        $this->enviar(['tipo_entrega' => 'en_sitio']);
        $this->enviar([
            'tipo_entrega' => 'domicilio',
            'direccion_entrega' => 'Calle Hidalgo 123',
        ]);

        $pedidos = Pedido::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertCount(2, $pedidos);

        [$enSitio, $domicilio] = [$pedidos[0], $pedidos[1]];
        $this->assertSame('domicilio', $domicilio->tipo_entrega->value);
        $this->assertSame((string) $enSitio->total, (string) $domicilio->total);
        $this->assertStringContainsString('(sin envío)', $this->mensaje($domicilio));
    }
}
