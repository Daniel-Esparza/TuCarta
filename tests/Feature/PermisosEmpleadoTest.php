<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
    }

    // Las rutas viven en el panel del subdominio del negocio.
    private function panel(Negocio $negocio, string $ruta): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/panel'.$ruta;
    }

    /**
     * @param  list<ClavePermiso>  $claves
     */
    private function empleadoConPermisos(Negocio $negocio, array $claves): Usuario
    {
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        foreach ($claves as $clave) {
            $empleado->otorgarPermiso($clave, $negocio);
        }

        return $empleado;
    }

    public function test_el_empleado_entra_a_las_rutas_de_pedidos_y_agotado(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::AvanzarPedidos, ClavePermiso::MarcarAgotado]);

        $this->actingAs($empleado);

        $this->get($this->panel($negocio, '/prueba/avanzar-pedidos'))->assertOk();
        $this->get($this->panel($negocio, '/prueba/marcar-agotado'))->assertOk();
    }

    public function test_el_empleado_recibe_403_en_metricas_precios_y_perfil(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = $this->empleadoConPermisos($negocio, [ClavePermiso::AvanzarPedidos, ClavePermiso::MarcarAgotado]);

        $this->actingAs($empleado);

        $this->get($this->panel($negocio, '/prueba/ver-metricas'))->assertForbidden();
        $this->get($this->panel($negocio, '/prueba/editar-precios'))->assertForbidden();
        $this->get($this->panel($negocio, '/prueba/editar-perfil'))->assertForbidden();
    }

    public function test_el_empleado_sin_permisos_no_entra_a_ninguna_ruta(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado);

        $this->get($this->panel($negocio, '/prueba/avanzar-pedidos'))->assertForbidden();
        $this->get($this->panel($negocio, '/prueba/marcar-agotado'))->assertForbidden();
        $this->get($this->panel($negocio, '/prueba/ver-metricas'))->assertForbidden();
    }

    public function test_el_dueno_con_todos_los_permisos_entra_a_todo(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $dueno->otorgarPermisosDeDueno();

        $this->actingAs($dueno);

        $this->get($this->panel($negocio, '/prueba/avanzar-pedidos'))->assertOk();
        $this->get($this->panel($negocio, '/prueba/marcar-agotado'))->assertOk();
        $this->get($this->panel($negocio, '/prueba/ver-metricas'))->assertOk();
        $this->get($this->panel($negocio, '/prueba/editar-precios'))->assertOk();
        $this->get($this->panel($negocio, '/prueba/editar-perfil'))->assertOk();
        $this->get($this->panel($negocio, '/equipo'))->assertOk();
    }

    public function test_un_invitado_sin_sesion_va_al_login(): void
    {
        $negocio = Negocio::factory()->create();
        $login = 'http://auth.'.config('tucarta.dominio');

        $this->get($this->panel($negocio, '/prueba/avanzar-pedidos'))->assertRedirect($login);
        $this->get($this->panel($negocio, '/equipo'))->assertRedirect($login);
    }

    public function test_el_permiso_de_otro_negocio_no_abre_la_ruta(): void
    {
        $tacos = Negocio::factory()->create();
        $sushi = Negocio::factory()->create();

        $empleadoDeTacos = Usuario::factory()->deNegocio($tacos)->create();
        $empleadoDeTacos->otorgarPermiso(ClavePermiso::AvanzarPedidos, $sushi);

        $this->actingAs($empleadoDeTacos)
            ->get($this->panel($tacos, '/prueba/avanzar-pedidos'))
            ->assertForbidden();
    }

    public function test_un_permiso_global_aplica_sin_importar_el_negocio(): void
    {
        $tacos = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($tacos)->create();
        $empleado->otorgarPermiso(ClavePermiso::AvanzarPedidos);

        $this->actingAs($empleado)
            ->get($this->panel($tacos, '/prueba/avanzar-pedidos'))
            ->assertOk();
    }
}
