<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Usuario;
use App\Support\NegocioActual;
use Database\Seeders\NucleoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AislamientoPorNegocioTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $tacos;

    private Negocio $sushi;

    private Categoria $categoriaTacos;

    private Categoria $categoriaSushi;

    private Usuario $juan;

    protected function setUp(): void
    {
        parent::setUp();

        // Los 2 negocios del seeder, con una categoría y un producto cada uno.
        $this->seed(NucleoSeeder::class);

        $this->tacos = Negocio::where('subdominio', 'tacos-juan')->firstOrFail();
        $this->sushi = Negocio::where('subdominio', 'sushi-ana')->firstOrFail();
        $this->juan = Usuario::where('email', 'juan@tacos-juan.tucarta.local')->firstOrFail();

        $this->categoriaTacos = Categoria::create(['negocio_id' => $this->tacos->id, 'nombre' => 'Tacos', 'orden' => 1]);
        $this->categoriaSushi = Categoria::create(['negocio_id' => $this->sushi->id, 'nombre' => 'Rollos', 'orden' => 1]);

        Item::create(['negocio_id' => $this->tacos->id, 'categoria_id' => $this->categoriaTacos->id, 'nombre' => 'Taco al pastor', 'precio' => 25]);
        Item::create(['negocio_id' => $this->sushi->id, 'categoria_id' => $this->categoriaSushi->id, 'nombre' => 'Rollo california', 'precio' => 120]);
    }

    private function url(string $subdominio, string $ruta = '/'): string
    {
        return 'http://'.($subdominio === '' ? '' : $subdominio.'.').config('tucarta.dominio').$ruta;
    }

    public function test_la_landing_sigue_en_el_dominio_principal(): void
    {
        $this->get($this->url(''))->assertOk()->assertSee('Registrar mi negocio');
        $this->get('http://localhost/')->assertOk()->assertSee('Registrar mi negocio');
    }

    public function test_cada_subdominio_abre_su_propio_negocio(): void
    {
        $this->get($this->url('tacos-juan'))->assertOk()->assertSee('Tacos Juan')->assertDontSee('Sushi Ana');
        $this->get($this->url('sushi-ana'))->assertOk()->assertSee('Sushi Ana')->assertDontSee('Tacos Juan');
    }

    public function test_un_subdominio_que_no_existe_responde_404_propio(): void
    {
        $this->get($this->url('no-existe'))->assertNotFound()->assertSee('Página no disponible');
    }

    public function test_los_subdominios_reservados_no_son_negocios(): void
    {
        foreach (config('tucarta.reservados') as $reservado) {
            if ($reservado === 'auth') {
                // auth es el login unificado, no un negocio
                $this->get($this->url($reservado))->assertOk()->assertSee('Iniciar Sesión');

                continue;
            }

            $this->get($this->url($reservado))->assertNotFound();
        }
    }

    public function test_un_negocio_suspendido_o_pendiente_no_abre_y_no_se_borra_nada(): void
    {
        $this->tacos->update(['estado' => 'suspendido']);
        $this->get($this->url('tacos-juan'))->assertOk()->assertSee('no está disponible por el momento');
        $this->actingAs($this->juan)->get($this->url('tacos-juan', '/panel'))->assertDontSee('Hola Negocio');

        $this->tacos->update(['estado' => 'pendiente']);
        $this->get($this->url('tacos-juan'))->assertNotFound()->assertDontSee('Tacos Juan');

        $this->assertDatabaseHas('categorias', ['negocio_id' => $this->tacos->id]);
    }

    public function test_sin_sesion_el_panel_manda_al_login_unificado(): void
    {
        $this->get($this->url('tacos-juan', '/panel'))
            ->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }

    public function test_el_dueno_ve_solo_la_informacion_de_su_negocio(): void
    {
        $this->actingAs($this->juan)
            ->get($this->url('tacos-juan', '/panel'))
            ->assertOk()
            ->assertSee('Hola Negocio')
            ->assertSee('Tacos')
            ->assertDontSee('Rollos');
    }

    public function test_el_dueno_no_puede_abrir_el_panel_de_otro_negocio(): void
    {
        $this->actingAs($this->juan)->get($this->url('sushi-ana', '/panel'))->assertNotFound();
    }

    public function test_cambiar_el_id_en_la_direccion_responde_404_y_no_403(): void
    {
        $this->actingAs($this->juan)
            ->get($this->url('tacos-juan', '/panel/categorias/'.$this->categoriaSushi->id))
            ->assertNotFound();

        $this->actingAs($this->juan)
            ->get($this->url('tacos-juan', '/panel/categorias/'.$this->categoriaTacos->id))
            ->assertOk()
            ->assertSee('Taco al pastor');
    }

    public function test_el_dueno_no_entra_al_panel_del_administrador(): void
    {
        $this->actingAs($this->juan)->get($this->url('auth', '/panel'))->assertNotFound();
    }

    public function test_el_administrador_ve_todos_los_negocios_y_no_entra_al_panel_de_un_negocio(): void
    {
        $admin = Usuario::where('email', 'admin@tucarta.local')->firstOrFail();

        $this->actingAs($admin)
            ->get($this->url('auth', '/panel'))
            ->assertOk()
            ->assertSee('Hola Administrador')
            ->assertSee('tacos-juan')
            ->assertSee('sushi-ana');

        $this->actingAs($admin)->get($this->url('tacos-juan', '/panel'))->assertNotFound();
    }

    public function test_un_cliente_no_entra_a_ningun_panel(): void
    {
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente)->get($this->url('tacos-juan', '/panel'))->assertNotFound();
        $this->actingAs($cliente)->get($this->url('auth', '/panel'))->assertNotFound();
    }

    public function test_una_consulta_sin_filtro_manual_solo_regresa_filas_del_negocio_actual(): void
    {
        app(NegocioActual::class)->establecer($this->tacos);

        $this->assertSame(['Tacos'], Categoria::pluck('nombre')->all());
        $this->assertSame(['Taco al pastor'], Item::pluck('nombre')->all());
    }

    public function test_al_crear_se_usa_el_negocio_actual_aunque_manden_otro(): void
    {
        app(NegocioActual::class)->establecer($this->tacos);

        $categoria = Categoria::create(['negocio_id' => $this->sushi->id, 'nombre' => 'Intento']);

        $this->assertSame($this->tacos->id, $categoria->negocio_id);
    }
}
