<?php

namespace Tests\Feature;

use App\Enums\TipoEntrega;
use App\Models\GrupoOpcion;
use App\Models\Horario;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Opcion;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\PedidoItemOpcion;
use App\Services\WhatsappPedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappPedidoTest extends TestCase
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

    private function url(string $ruta = '/pedido'): string
    {
        return 'http://pizza-nova.'.config('tucarta.dominio').$ruta;
    }

    private function opcionesObligatorias(Item $item): array
    {
        return GrupoOpcion::withoutGlobalScopes()
            ->where('item_id', $item->id)
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
    }

    public function test_el_mensaje_incluye_folio_cliente_productos_opciones_total_y_nota(): void
    {
        $pedido = Pedido::create([
            'negocio_id' => $this->pizzeria->id,
            'folio' => 'A-0147',
            'cliente_nombre' => 'Ana Martínez',
            'notas' => 'Sin picante por favor',
            'tipo_entrega' => TipoEntrega::EnSitio,
            'total' => 235.00,
        ]);

        $item1 = PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $this->pizzeria->id,
            'item_id' => $this->margarita->id,
            'nombre_item' => 'Pizza Margarita',
            'precio_unitario' => 100.00,
            'cantidad' => 2,
            'subtotal' => 200.00,
        ]);

        PedidoItemOpcion::create([
            'pedido_item_id' => $item1->id,
            'negocio_id' => $this->pizzeria->id,
            'nombre_grupo' => 'Tamaño',
            'nombre_opcion' => 'Grande',
            'incremento_precio' => 20.00,
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $this->pizzeria->id,
            'item_id' => $this->margarita->id,
            'nombre_item' => 'Agua de horchata',
            'precio_unitario' => 35.00,
            'cantidad' => 1,
            'subtotal' => 35.00,
        ]);

        $servicio = new WhatsappPedidoService;
        $mensaje = $servicio->construirMensaje($pedido, $this->pizzeria);

        $this->assertStringContainsString("Pedido A-0147 - {$this->pizzeria->nombre}", $mensaje);
        $this->assertStringContainsString('Cliente: Ana Martínez', $mensaje);
        $this->assertStringContainsString('2 x Pizza Margarita ... $200', $mensaje);
        $this->assertStringContainsString('   Opciones: Grande', $mensaje);
        $this->assertStringContainsString('1 x Agua de horchata ... $35', $mensaje);
        $this->assertStringContainsString('Total estimado: $235 (sin envío)', $mensaje);
        $this->assertStringContainsString('Nota: Sin picante por favor', $mensaje);
    }

    public function test_el_mensaje_omite_nota_si_esta_vacia_y_no_imprime_opciones_si_no_hay(): void
    {
        $pedido = Pedido::create([
            'negocio_id' => $this->pizzeria->id,
            'folio' => 'A-0148',
            'cliente_nombre' => 'Carlos Ruiz',
            'notas' => null,
            'tipo_entrega' => TipoEntrega::ParaLlevar,
            'total' => 90.00,
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $this->pizzeria->id,
            'item_id' => $this->margarita->id,
            'nombre_item' => 'Refresco',
            'precio_unitario' => 30.00,
            'cantidad' => 3,
            'subtotal' => 90.00,
        ]);

        $servicio = new WhatsappPedidoService;
        $mensaje = $servicio->construirMensaje($pedido, $this->pizzeria);

        $this->assertStringNotContainsString('Opciones:', $mensaje);
        $this->assertStringNotContainsString('Nota:', $mensaje);
        $this->assertStringContainsString('Total estimado: $90 (sin envío)', $mensaje);
    }

    public function test_incluye_bloque_de_tipo_entrega_cuando_existe(): void
    {
        $pedido = Pedido::create([
            'negocio_id' => $this->pizzeria->id,
            'folio' => 'A-0149',
            'cliente_nombre' => 'Lucía Gómez',
            'tipo_entrega' => TipoEntrega::Domicilio,
            'direccion_entrega' => 'Av. Carranza 123',
            'referencias_entrega' => 'Portón negro',
            'total' => 150.00,
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $this->pizzeria->id,
            'item_id' => $this->margarita->id,
            'nombre_item' => 'Pizza Margarita',
            'precio_unitario' => 150.00,
            'cantidad' => 1,
            'subtotal' => 150.00,
        ]);

        $mensaje = (new WhatsappPedidoService)->construirMensaje($pedido, $this->pizzeria);

        $this->assertStringContainsString('Tipo de entrega: A domicilio', $mensaje);
        $this->assertStringContainsString('Dirección: Av. Carranza 123', $mensaje);
        $this->assertStringContainsString('Referencias: Portón negro', $mensaje);
    }

    public function test_normaliza_telefono_y_codifica_url_de_whatsapp(): void
    {
        $this->pizzeria->update(['telefono_whatsapp' => '+52 (444) 123-4567']);

        $pedido = Pedido::create([
            'negocio_id' => $this->pizzeria->id,
            'folio' => 'A-0150',
            'cliente_nombre' => 'José Ángel Pérez',
            'notas' => 'Con piña & jalapeño',
            'tipo_entrega' => TipoEntrega::EnSitio,
            'total' => 120.50,
        ]);

        PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $this->pizzeria->id,
            'item_id' => $this->margarita->id,
            'nombre_item' => 'Pizza Hawaiana',
            'precio_unitario' => 120.50,
            'cantidad' => 1,
            'subtotal' => 120.50,
        ]);

        $servicio = new WhatsappPedidoService;

        $this->assertSame('524449876543', $servicio->normalizarTelefono('444-987-6543'));
        $this->assertNull($servicio->normalizarTelefono(''));

        $url = $servicio->generarUrl($pedido, $this->pizzeria);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/524441234567?text=', $url);
        $this->assertStringContainsString('%0A', $url);
        $this->assertStringContainsString('%20', $url);
        $this->assertStringNotContainsString('+', explode('?text=', $url)[1]);
    }

    public function test_genera_url_valida_para_pedido_grande_de_15_productos(): void
    {
        $this->pizzeria->update(['telefono_whatsapp' => '4441234567']);

        $pedido = Pedido::create([
            'negocio_id' => $this->pizzeria->id,
            'folio' => 'A-0151',
            'cliente_nombre' => 'Evento Corporativo',
            'tipo_entrega' => TipoEntrega::ParaLlevar,
            'total' => 1500.00,
        ]);

        foreach (range(1, 15) as $i) {
            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'negocio_id' => $this->pizzeria->id,
                'item_id' => $this->margarita->id,
                'nombre_item' => "Producto Especial Número {$i}",
                'precio_unitario' => 100.00,
                'cantidad' => 1,
                'subtotal' => 100.00,
            ]);
        }

        $url = (new WhatsappPedidoService)->generarUrl($pedido, $this->pizzeria);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/524441234567?text=', $url);
        $textoDecodificado = rawurldecode(explode('?text=', $url)[1]);
        $this->assertStringContainsString('Producto Especial Número 15', $textoDecodificado);
    }

    public function test_pantalla_gracias_muestra_boton_whatsapp_y_vacia_carrito(): void
    {
        $this->pizzeria->update(['telefono_whatsapp' => '4441234567']);

        $carrito = json_encode([
            [
                'item_id' => $this->margarita->id,
                'cantidad' => 2,
                'opciones' => $this->opcionesObligatorias($this->margarita),
            ],
        ]);

        $respuesta = $this->followingRedirects()->post($this->url('/pedido'), [
            'cliente_nombre' => 'María López',
            'notas' => 'Tocar el timbre',
            'carrito' => $carrito,
            'tipo_entrega' => 'en_sitio',
        ]);

        $respuesta->assertOk();
        $respuesta->assertSee('Abrir WhatsApp');
        $respuesta->assertSee('https://wa.me/524441234567?text=', false);
        $respuesta->assertSee('tucarta.carrito.'.$this->pizzeria->id, false);
    }

    public function test_pantalla_gracias_muestra_aviso_si_negocio_no_tiene_whatsapp(): void
    {
        $this->pizzeria->update(['telefono_whatsapp' => '']);

        $carrito = json_encode([
            [
                'item_id' => $this->margarita->id,
                'cantidad' => 1,
                'opciones' => $this->opcionesObligatorias($this->margarita),
            ],
        ]);

        $respuesta = $this->followingRedirects()->post($this->url('/pedido'), [
            'cliente_nombre' => 'Pedro Sánchez',
            'carrito' => $carrito,
            'tipo_entrega' => 'en_sitio',
        ]);

        $respuesta->assertOk();
        $respuesta->assertDontSee('Abrir WhatsApp');
        $respuesta->assertSee('no tiene un número de WhatsApp disponible');
    }

    public function test_pedido_de_otro_negocio_en_sesion_responde_404(): void
    {
        $otroNegocio = Negocio::where('subdominio', '!=', 'pizza-nova')->firstOrFail();

        $pedidoAjeno = Pedido::create([
            'negocio_id' => $otroNegocio->id,
            'folio' => 'A-9999',
            'cliente_nombre' => 'Cliente Ajeno',
            'tipo_entrega' => TipoEntrega::EnSitio,
            'total' => 100.00,
        ]);

        $this->withSession([
            'folio' => $pedidoAjeno->folio,
            'pedido_id' => $pedidoAjeno->id,
        ])->get($this->url('/pedido/gracias'))
            ->assertNotFound();
    }
}
