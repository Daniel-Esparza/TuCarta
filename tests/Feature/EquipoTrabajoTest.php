<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EquipoTrabajoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
    }

    private function duenoConPermisoDeEquipo(): array
    {
        $negocio = Negocio::factory()->create();

        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $dueno->otorgarPermiso(ClavePermiso::GestionarEquipo, $negocio);

        return [$negocio, $dueno];
    }

    // La pantalla vive en el panel del subdominio del negocio.
    private function equipo(Negocio $negocio, string $ruta = ''): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/panel/equipo'.$ruta;
    }

    private function datosDeEmpleado(array $extras = []): array
    {
        return array_merge([
            'nombre' => 'Maria',
            'apellidos' => 'Lopez',
            'email' => 'maria@example.com',
            'telefono_whatsapp' => '+52 55 1234 5678',
            'password' => '12345678',
        ], $extras);
    }

    public function test_el_dueno_ve_su_equipo_en_la_pantalla(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        Usuario::factory()->deNegocio($negocio)->create([
            'nombre' => 'Marco',
            'apellidos' => 'El del Mostrador',
        ]);

        $this->actingAs($dueno)->get($this->equipo($negocio))
            ->assertOk()
            ->assertSee('Equipo de trabajo')
            ->assertSee('Marco')
            ->assertSee($dueno->nombre);
    }

    public function test_la_pantalla_no_muestra_empleados_de_otros_negocios(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $otro = Negocio::factory()->create();
        Usuario::factory()->deNegocio($otro)->create([
            'nombre' => 'EspiaDelSushi',
            'apellidos' => 'Ajeno',
        ]);

        $this->actingAs($dueno)->get($this->equipo($negocio))
            ->assertOk()
            ->assertDontSee('EspiaDelSushi');
    }

    public function test_el_dueno_no_abre_el_equipo_de_otro_negocio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();
        $otro = Negocio::factory()->create();

        $this->actingAs($dueno)->get($this->equipo($otro))->assertNotFound();
    }

    public function test_el_dueno_crea_un_empleado_que_puede_iniciar_sesion(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $this->actingAs($dueno)
            ->post($this->equipo($negocio), $this->datosDeEmpleado())
            ->assertRedirect($this->equipo($negocio));

        $empleado = Usuario::where('email', 'maria@example.com')->first();
        $this->assertNotNull($empleado);
        $this->assertTrue($empleado->rol === Rol::Negocio);
        $this->assertEquals($negocio->id, $empleado->negocio_id);
        $this->assertEquals('activo', $empleado->estado);

        $this->assertTrue($empleado->tienePermiso(ClavePermiso::AvanzarPedidos));
        $this->assertTrue($empleado->tienePermiso(ClavePermiso::MarcarAgotado));
        $this->assertFalse($empleado->tienePermiso(ClavePermiso::VerMetricas));

        $this->assertTrue(Auth::attempt([
            'email' => 'maria@example.com',
            'password' => '12345678',
            'estado' => 'activo',
        ]));
        Auth::logout();
    }

    public function test_un_empleado_desactivado_ya_no_puede_iniciar_sesion(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $empleado->update(['estado' => 'inactivo']);

        $this->assertFalse(Auth::attempt([
            'email' => $empleado->email,
            'password' => 'password',
            'estado' => 'activo',
        ]));
    }

    public function test_el_dueno_desactiva_a_un_empleado(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($dueno)
            ->patch($this->equipo($negocio, '/'.$empleado->id.'/desactivar'))
            ->assertRedirect($this->equipo($negocio));

        $this->assertDatabaseHas('usuarios', [
            'id' => $empleado->id,
            'estado' => 'inactivo',
        ]);
    }

    public function test_el_dueno_no_puede_desactivar_a_un_empleado_de_otro_negocio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $otro = Negocio::factory()->create();
        $empleadoAjeno = Usuario::factory()->deNegocio($otro)->create();

        $this->actingAs($dueno)
            ->patch($this->equipo($negocio, '/'.$empleadoAjeno->id.'/desactivar'))
            ->assertNotFound();
    }

    public function test_el_dueno_no_puede_desactivar_su_propia_cuenta(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $this->actingAs($dueno)
            ->patch($this->equipo($negocio, '/'.$dueno->id.'/desactivar'))
            ->assertForbidden();
    }

    public function test_un_empleado_sin_permiso_no_ve_ni_usa_la_pantalla_de_equipo(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado)->get($this->equipo($negocio))->assertForbidden();
        $this->actingAs($empleado)->post($this->equipo($negocio), $this->datosDeEmpleado())->assertForbidden();
    }

    public function test_el_formulario_exige_todos_los_campos(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $this->actingAs($dueno)
            ->post($this->equipo($negocio), [])
            ->assertSessionHasErrors(['nombre', 'apellidos', 'email', 'telefono_whatsapp', 'password']);
    }

    public function test_no_se_puede_crear_un_empleado_con_un_correo_ya_registrado(): void
    {
        [$negocio, $dueno] = $this->duenoConPermisoDeEquipo();

        $this->actingAs($dueno)
            ->post($this->equipo($negocio), $this->datosDeEmpleado(['email' => $dueno->email]))
            ->assertSessionHasErrors('email');
    }
}
