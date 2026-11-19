<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegocioNoDisponibleTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $pizzeria;
    private Item $margarita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->pizzeria  = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();
        $this->margarita = Item::withoutGlobalScopes()->where('nombre', 'Pizza Margarita')->firstOrFail();
    }

    private function url(string $ruta = '/'): string
    {
        return 'http://'.$this->pizzeria->subdominio.'.'.config('tucarta.dominio').$ruta;
    }

    private function urlDetalle(): string
    {
        return $this->url('/producto/'.$this->margarita->id);
    }

    private function cambiarEstado(string $estado): void
    {
        $this->pizzeria->update(['estado' => $estado]);
    }

    public function test_una_direccion_inexistente_muestra_la_pagina_de_error_propia(): void
    {
        $this->get('http://no-existe.'.config('tucarta.dominio').'/')
            ->assertNotFound()
            ->assertSee('Página no disponible');
    }

    public function test_un_negocio_suspendido_muestra_el_aviso_sin_productos(): void
    {
        $this->cambiarEstado('suspendido');

        $this->get($this->url())
            ->assertOk()
            ->assertSee('no está disponible por el momento')
            ->assertDontSee($this->margarita->nombre);
    }

    public function test_el_detalle_de_un_producto_de_un_negocio_suspendido_muestra_el_aviso(): void
    {
        $this->cambiarEstado('suspendido');

        $this->get($this->urlDetalle())
            ->assertOk()
            ->assertSee('no está disponible por el momento')
            ->assertDontSee($this->margarita->nombre);
    }

    public function test_pendiente_y_rechazado_no_muestran_menu_y_responden_como_inexistente(): void
    {
        foreach (['pendiente', 'rechazado'] as $estado) {
            $this->cambiarEstado($estado);

            $this->get($this->url())
                ->assertNotFound()
                ->assertSee('Página no disponible')
                ->assertDontSee($this->margarita->nombre);

            $this->get($this->urlDetalle())
                ->assertNotFound()
                ->assertDontSee($this->margarita->nombre);
        }
    }

    public function test_al_reactivar_el_negocio_vuelve_a_abrir_su_detalle(): void
    {
        $this->cambiarEstado('suspendido');
        $this->get($this->urlDetalle())->assertDontSee($this->margarita->nombre);

        $this->cambiarEstado('activo');
        $this->get($this->urlDetalle())
            ->assertOk()
            ->assertSee($this->margarita->nombre);
    }
}
