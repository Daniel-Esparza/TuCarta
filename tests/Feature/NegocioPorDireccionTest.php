<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NegocioPorDireccionTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $tacos;

    private Negocio $sushi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->tacos = Negocio::where('subdominio', 'tacos-juan')->firstOrFail();
        $this->sushi = Negocio::where('subdominio', 'sushi-ana')->firstOrFail();
    }

    private function url(string $subdominio, string $ruta = '/'): string
    {
        return 'http://'.$subdominio.'.'.config('tucarta.dominio').$ruta;
    }

    private function producto(string $nombre): Item
    {
        return Item::withoutGlobalScopes()->where('nombre', $nombre)->firstOrFail();
    }

    public function test_la_direccion_de_un_negocio_activo_carga_ese_negocio(): void
    {
        $this->get($this->url('tacos-juan'))
            ->assertOk()
            ->assertSee('Tacos Juan')
            ->assertSee('Taco al pastor')
            ->assertSee('Agua de horchata');
    }

    public function test_dos_negocios_muestran_cada_uno_solo_sus_categorias_y_productos(): void
    {
        $this->get($this->url('tacos-juan'))
            ->assertOk()
            ->assertSee('Taco al pastor')
            ->assertDontSee('Rollos')
            ->assertDontSee('Rollo California');

        $this->get($this->url('sushi-ana'))
            ->assertOk()
            ->assertSee('Rollos')
            ->assertSee('Rollo California')
            ->assertDontSee('Taco al pastor')
            ->assertDontSee('Tacos Juan');
    }

    public function test_una_palabra_reservada_no_carga_ningun_negocio(): void
    {
        foreach (['admin', 'api', 'app', 'mail', 'traefik'] as $reservada) {
            // Aunque existiera un negocio con ese nombre, la dirección no lo abre.
            Negocio::factory()->create(['subdominio' => $reservada, 'nombre' => "Negocio {$reservada}"]);

            $this->get($this->url($reservada))
                ->assertNotFound()
                ->assertSee('Página no disponible')
                ->assertDontSee("Negocio {$reservada}");
        }
    }

    public function test_www_lleva_a_la_pagina_principal(): void
    {
        $this->get($this->url('www', '/registrar-negocio?origen=1'))
            ->assertStatus(301)
            ->assertRedirect('http://'.config('tucarta.dominio').'/registrar-negocio?origen=1');
    }

    public function test_la_landing_y_el_registro_no_se_abren_en_la_direccion_de_un_negocio(): void
    {
        $this->get($this->url('tacos-juan', '/registrar-negocio'))->assertNotFound();
        $this->post($this->url('tacos-juan', '/registrar-negocio'), [])->assertNotFound();

        $this->get('http://'.config('tucarta.dominio').'/registrar-negocio')->assertOk();
    }

    public function test_la_direccion_no_distingue_mayusculas(): void
    {
        $this->get($this->url('Tacos-Juan'))
            ->assertOk()
            ->assertSee('Taco al pastor');
    }

    public function test_el_identificador_de_otro_negocio_en_la_peticion_no_cambia_el_negocio(): void
    {
        $this->get($this->url('tacos-juan', '/?negocio_id='.$this->sushi->id.'&negocio=sushi-ana&subdominio=sushi-ana'))
            ->assertOk()
            ->assertSee('Taco al pastor')
            ->assertDontSee('Rollo California');

        $rollo = $this->producto('Rollo California');

        $this->get($this->url('tacos-juan', '/producto/'.$rollo->id.'?negocio_id='.$this->sushi->id))
            ->assertNotFound()
            ->assertDontSee('Rollo California');
    }

    public function test_el_detalle_abre_desde_el_menu_de_su_negocio(): void
    {
        $taco = $this->producto('Taco al pastor');

        $this->get($this->url('tacos-juan'))
            ->assertSee($this->url('tacos-juan', '/producto/'.$taco->id), false);

        $this->get($this->url('tacos-juan', '/producto/'.$taco->id))
            ->assertOk()
            ->assertSee('Taco al pastor')
            ->assertSee('Queso');
    }

    public function test_un_producto_agotado_se_ve_apagado_en_el_menu(): void
    {
        $this->get($this->url('tacos-juan'))
            ->assertOk()
            ->assertSeeInOrder(['Agua de jamaica', 'Agotado']);
    }

    public function test_un_producto_inactivo_no_abre_por_su_direccion(): void
    {
        $taco = $this->producto('Gringa');
        $taco->update(['activo' => false]);

        $this->get($this->url('tacos-juan', '/producto/'.$taco->id))->assertNotFound();
    }

    public function test_las_consultas_del_menu_usan_el_rol_de_la_aplicacion_y_el_negocio_de_la_direccion(): void
    {
        Route::domain('{subdominio}.'.config('tucarta.dominio'))
            ->middleware(['web', 'negocio'])
            ->get('/prueba/rol', fn () => [
                'rol' => DB::selectOne('select current_user as rol')->rol,
                'negocio' => (int) DB::selectOne("select current_setting('app.negocio_id') as id")->id,
                // Consulta directa, sin el filtro de Eloquent: solo responde el RLS.
                'productos' => DB::table('items')->pluck('nombre')->all(),
            ]);

        $respuesta = $this->getJson($this->url('sushi-ana', '/prueba/rol'))->assertOk();

        $respuesta->assertJson(['rol' => 'tucarta_app', 'negocio' => $this->sushi->id]);
        $this->assertContains('Rollo California', $respuesta->json('productos'));
        $this->assertNotContains('Taco al pastor', $respuesta->json('productos'));
        $this->assertNotContains('Pizza Margarita', $respuesta->json('productos'));
    }
}
