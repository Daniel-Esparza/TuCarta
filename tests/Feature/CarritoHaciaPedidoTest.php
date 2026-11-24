<?php

namespace Tests\Feature;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CarritoHaciaPedidoTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        // Miércoles 8 de la noche: Tacos Juan está abierto.
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00', 'America/Mexico_City'));

        $this->seed();
        $this->negocio = Negocio::where('subdominio', 'tacos-juan')->firstOrFail();
    }

    private function url(string $ruta): string
    {
        return 'http://tacos-juan.'.config('tucarta.dominio').$ruta;
    }

    public function test_el_carrito_del_menu_sabe_a_donde_ir_para_pedir(): void
    {
        $this->get($this->url('/'))
            ->assertOk()
            ->assertSee('"url_pedido":"'.str_replace('/', '\/', $this->url('/pedido')).'"', false);
    }

    public function test_la_confirmacion_lee_el_carrito_de_este_negocio_y_lo_manda_como_espera_el_servidor(): void
    {
        $this->get($this->url('/pedido'))
            ->assertOk()
            ->assertSee('"tucarta.carrito.'.$this->negocio->id.'"', false)
            ->assertSee('item_id: l.producto_id', false)
            ->assertSee('id="resumen-carrito"', false);
    }
}
