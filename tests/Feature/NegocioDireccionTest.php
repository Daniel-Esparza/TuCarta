<?php

namespace Tests\Feature;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegocioDireccionTest extends TestCase
{
    use RefreshDatabase;

    private function url(string $subdominio): string
    {
        return 'http://'.$subdominio.'.'.config('tucarta.dominio').'/';
    }

    private function negocio(string $subdominio, string $estado, string $nombre = 'Negocio de prueba'): Negocio
    {
        return Negocio::factory()->create([
            'subdominio' => $subdominio,
            'estado' => $estado,
            'nombre' => $nombre,
        ]);
    }

    public function test_negocio_activo_muestra_la_pagina_provisional(): void
    {
        $this->negocio('tacos-juan', 'activo', 'Tacos Juan');

        $this->get($this->url('tacos-juan'))
            ->assertOk()
            ->assertSee('Tacos Juan')
            ->assertSee('Estamos preparando la carta');
    }

    public function test_negocio_suspendido_muestra_su_aviso(): void
    {
        $this->negocio('sushi-ana', 'suspendido', 'Sushi Ana');

        $this->get($this->url('sushi-ana'))
            ->assertOk()
            ->assertSee('Sushi Ana')
            ->assertSee('no está disponible por el momento');
    }

    public function test_pendiente_rechazado_e_inexistente_muestran_la_misma_pagina_de_error(): void
    {
        $this->negocio('pendiente-uno', 'pendiente', 'Nombre Secreto');
        $this->negocio('rechazado-uno', 'rechazado', 'Nombre Secreto');

        foreach (['pendiente-uno', 'rechazado-uno', 'no-existe'] as $subdominio) {
            $this->get($this->url($subdominio))
                ->assertNotFound()
                ->assertSee('Página no disponible')
                ->assertDontSee('Nombre Secreto');
        }
    }

    public function test_dos_negocios_activos_no_se_mezclan(): void
    {
        $this->negocio('tacos-juan', 'activo', 'Tacos Juan');
        $this->negocio('sushi-ana', 'activo', 'Sushi Ana');

        $this->get($this->url('tacos-juan'))->assertSee('Tacos Juan')->assertDontSee('Sushi Ana');
        $this->get($this->url('sushi-ana'))->assertSee('Sushi Ana')->assertDontSee('Tacos Juan');
    }

    public function test_el_panel_central_no_cae_en_la_pagina_de_error(): void
    {
        $this->get($this->url('auth'))->assertRedirect(route('panel'));
    }

    public function test_un_negocio_recien_creado_abre_en_su_direccion_sin_configuracion(): void
    {
        $this->negocio('cafe-nuevo', 'activo', 'Cafe Nuevo');

        $this->get($this->url('cafe-nuevo'))->assertOk()->assertSee('Cafe Nuevo');
    }
}