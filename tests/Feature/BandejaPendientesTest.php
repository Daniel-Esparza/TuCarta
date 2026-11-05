<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\EstadoNegocio;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BandejaPendientesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);

        $this->admin = Usuario::factory()->administrador()->create();
        $this->admin->otorgarPermiso(ClavePermiso::GestionarNegocios);
    }

    private function url(string $ruta = ''): string
    {
        return 'http://auth.'.config('tucarta.dominio').'/panel/negocios/pendientes'.$ruta;
    }

    private function accion(Negocio $negocio, string $accion): string
    {
        return 'http://auth.'.config('tucarta.dominio')."/panel/negocios/{$negocio->id}/{$accion}";
    }

    private function pendiente(array $datos = []): Negocio
    {
        return Negocio::factory()->pendiente()->create(array_merge([
            'nombre' => 'Tacos El Güero',
            'subdominio' => 'tacos-guero',
            'correo_contacto' => 'Contacto@TacosGuero.mx',
            'direccion' => null,
            'categoria' => null,
        ], $datos));
    }

    public function test_la_bandeja_solo_muestra_pendientes(): void
    {
        $this->pendiente();
        Negocio::factory()->create(['nombre' => 'Sushi Activo']);
        Negocio::factory()->suspendido()->create(['nombre' => 'Cafe Suspendido']);
        Negocio::factory()->rechazado()->create(['nombre' => 'Pizza Rechazada']);

        $this->actingAs($this->admin)->get($this->url())
            ->assertOk()
            ->assertSee('Tacos El Güero')
            ->assertDontSee('Sushi Activo')
            ->assertDontSee('Cafe Suspendido')
            ->assertDontSee('Pizza Rechazada');
    }

    public function test_aprobar_deja_el_negocio_activo_y_su_direccion_abre(): void
    {
        $negocio = $this->pendiente();
        $direccion = 'http://tacos-guero.'.config('tucarta.dominio');

        $this->get($direccion)->assertNotFound();

        $this->actingAs($this->admin)->patch($this->accion($negocio, 'aprobar'))
            ->assertRedirect()
            ->assertSessionHas('aprobado');

        $this->assertSame(EstadoNegocio::Activo, $negocio->fresh()->estado);
        $this->get($direccion)->assertOk()->assertSee('Tacos El Güero');
    }

    public function test_aprobar_crea_la_cuenta_del_dueno_con_contrasena_temporal_y_puede_entrar(): void
    {
        $negocio = $this->pendiente();

        $respuesta = $this->actingAs($this->admin)->patch($this->accion($negocio, 'aprobar'));
        $contrasena = $respuesta->getSession()->get('aprobado')['contrasena'];

        $dueno = Usuario::where('email', 'contacto@tacosguero.mx')->firstOrFail();
        $this->assertSame(Rol::Negocio, $dueno->rol);
        $this->assertSame($negocio->id, $dueno->negocio_id);
        $this->assertTrue(Hash::check($contrasena, $dueno->password));
        $this->assertTrue($dueno->tienePermiso(ClavePermiso::GestionarEquipo, $negocio));
        $this->assertDatabaseHas('usuario_permisos', ['usuario_id' => $dueno->id, 'asignado_por' => $this->admin->id]);

        auth()->logout();
        $this->post('http://auth.'.config('tucarta.dominio'), ['email' => 'contacto@tacosguero.mx', 'password' => $contrasena])
            ->assertRedirect('http://tacos-guero.'.config('tucarta.dominio').'/panel');
    }

    public function test_la_contrasena_temporal_se_muestra_una_vez(): void
    {
        $negocio = $this->pendiente();

        $this->actingAs($this->admin)->followingRedirects()->patch($this->accion($negocio, 'aprobar'))
            ->assertOk()
            ->assertSee('Contraseña temporal');

        $this->actingAs($this->admin)->get($this->url())->assertDontSee('Contraseña temporal');
    }

    public function test_aprobar_funciona_con_el_rol_real_de_la_aplicacion(): void
    {
        $negocio = $this->pendiente();
        $rolDurante = null;
        Usuario::created(function () use (&$rolDurante) {
            $rolDurante = DB::selectOne('select current_user as rol')->rol;
        });

        $this->actingAs($this->admin)->patch($this->accion($negocio, 'aprobar'))->assertSessionHas('aprobado');

        $this->assertSame('tucarta_app', $rolDurante);
    }

    public function test_si_el_correo_ya_tiene_cuenta_no_se_aprueba(): void
    {
        Usuario::factory()->create(['email' => 'contacto@tacosguero.mx']);
        $negocio = $this->pendiente();

        $this->actingAs($this->admin)->patch($this->accion($negocio, 'aprobar'))->assertSessionHas('error');

        $this->assertSame(EstadoNegocio::Pendiente, $negocio->fresh()->estado);
        $this->assertSame(0, $negocio->usuarios()->count());
    }

    public function test_si_falla_la_creacion_del_dueno_el_negocio_sigue_pendiente(): void
    {
        $negocio = $this->pendiente();
        Usuario::creating(fn () => throw new \RuntimeException('falla simulada'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin)->patch($this->accion($negocio, 'aprobar'));
            $this->fail('Se esperaba que fallara la creación del dueño.');
        } catch (\RuntimeException $e) {
            $this->assertSame('falla simulada', $e->getMessage());
        }

        $this->assertSame(EstadoNegocio::Pendiente, $negocio->fresh()->estado);
    }

    public function test_rechazar_sin_motivo_da_error_y_no_cambia_nada(): void
    {
        $negocio = $this->pendiente();

        $this->actingAs($this->admin)->from($this->url())->patch($this->accion($negocio, 'rechazar'), ['motivo' => ''])
            ->assertSessionHasErrors(['motivo' => 'Escribe el motivo para rechazar Tacos El Güero.']);

        $this->assertSame(EstadoNegocio::Pendiente, $negocio->fresh()->estado);
        $this->assertNull($negocio->fresh()->motivo_rechazo);
    }

    public function test_rechazar_con_motivo_lo_guarda_y_no_crea_cuenta(): void
    {
        $negocio = $this->pendiente();

        $this->actingAs($this->admin)->patch($this->accion($negocio, 'rechazar'), ['motivo' => 'Datos incompletos'])
            ->assertSessionHasNoErrors();

        $negocio->refresh();
        $this->assertSame(EstadoNegocio::Rechazado, $negocio->estado);
        $this->assertSame('Datos incompletos', $negocio->motivo_rechazo);
        $this->assertSame(0, $negocio->usuarios()->count());
        $this->get('http://tacos-guero.'.config('tucarta.dominio'))->assertNotFound();
    }

    public function test_la_direccion_de_un_negocio_rechazado_queda_libre(): void
    {
        $negocio = $this->pendiente();
        $this->actingAs($this->admin)->patch($this->accion($negocio, 'rechazar'), ['motivo' => 'Datos incompletos']);
        auth()->logout();

        $this->post('/registrar-negocio', [
            'nombre' => 'Otro Negocio',
            'direccion_web' => 'tacos-guero',
            'whatsapp' => '+524441234567',
            'correo_contacto' => 'otro@negocio.mx',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Negocio::where('subdominio', 'tacos-guero')->count());

        // Al aprobar el nuevo, la dirección abre el nuevo y no el rechazado.
        $nuevo = Negocio::where('nombre', 'Otro Negocio')->firstOrFail();
        $this->actingAs($this->admin)->patch($this->accion($nuevo, 'aprobar'));

        $this->get('http://tacos-guero.'.config('tucarta.dominio'))->assertOk()->assertSee('Otro Negocio');
    }

    public function test_dos_negocios_vigentes_no_pueden_compartir_direccion(): void
    {
        $this->pendiente();

        $this->expectException(\Illuminate\Database\QueryException::class);
        Negocio::factory()->create(['subdominio' => 'tacos-guero']);
    }

    public function test_solo_se_aprueban_o_rechazan_negocios_pendientes(): void
    {
        $activo = Negocio::factory()->create();
        $rechazado = Negocio::factory()->rechazado()->create();

        $this->actingAs($this->admin)->patch($this->accion($activo, 'aprobar'))->assertNotFound();
        $this->actingAs($this->admin)->patch($this->accion($activo, 'rechazar'), ['motivo' => 'X'])->assertNotFound();
        $this->actingAs($this->admin)->patch($this->accion($rechazado, 'aprobar'))->assertNotFound();
    }

    public function test_un_moderador_sin_permiso_global_no_entra_a_la_bandeja(): void
    {
        $negocio = $this->pendiente();
        $moderador = Usuario::factory()->administrador()->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, $negocio);

        $this->actingAs($moderador)->get($this->url())->assertForbidden();
        $this->actingAs($moderador)->patch($this->accion($negocio, 'aprobar'))->assertForbidden();
        $this->actingAs($moderador)->patch($this->accion($negocio, 'rechazar'), ['motivo' => 'X'])->assertForbidden();

        $this->assertSame(EstadoNegocio::Pendiente, $negocio->fresh()->estado);
    }

    public function test_cuentas_de_negocio_e_invitados_no_entran(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();

        $this->actingAs($dueno)->get($this->url())->assertNotFound();

        auth()->logout();
        $this->get($this->url())->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }

    public function test_el_alta_directa_sigue_disponible(): void
    {
        $this->actingAs($this->admin)->get(route('negocios.create'))->assertOk();
    }

    public function test_un_pendiente_no_se_puede_activar_desde_la_lista_general(): void
    {
        $negocio = $this->pendiente();

        $this->actingAs($this->admin)->patch($this->accion($negocio, 'suspender'))->assertNotFound();
        $this->actingAs($this->admin)->patch($this->accion($negocio, 'reactivar'))->assertNotFound();

        $this->assertSame(EstadoNegocio::Pendiente, $negocio->fresh()->estado);
        $this->actingAs($this->admin)->get(route('negocios.index'))->assertDontSee('Suspender este negocio');
    }
}
