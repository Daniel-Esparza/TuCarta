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
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AltaNegocioTest extends TestCase
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
        return 'http://auth.'.config('tucarta.dominio').'/panel/negocios'.$ruta;
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Tacos El Güero',
            'direccion_web' => 'tacos-guero',
            'whatsapp' => '+52 444 123 4567',
            'ubicacion' => 'Av. Carranza 100, Centro',
            'descripcion' => 'Tacos de pastor y suadero',
            'nombre_dueno' => 'Luis',
            'apellidos_dueno' => 'Pérez',
            'email' => 'Luis@TacosGuero.mx',
            'password' => 'secreta123',
        ], $cambios);
    }

    private function enviar(array $datos, ?Usuario $quien = null)
    {
        return $this->actingAs($quien ?? $this->admin)->from($this->url('/crear'))->post($this->url(), $datos);
    }

    public function test_el_formulario_muestra_los_campos_y_el_dominio_de_la_configuracion(): void
    {
        $this->actingAs($this->admin)->get($this->url('/crear'))
            ->assertOk()
            ->assertSee('Alta de negocio')
            ->assertSee('name="direccion_web"', false)
            ->assertSee('name="whatsapp"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(config('tucarta.dominio'));
    }

    public function test_un_administrador_con_permiso_crea_el_negocio_activo_con_su_dueno(): void
    {
        $this->enviar($this->datos())
            ->assertRedirect(route('negocios.index'))
            ->assertSessionHas('estado');

        $negocio = Negocio::where('subdominio', 'tacos-guero')->firstOrFail();
        $this->assertSame(EstadoNegocio::Activo, $negocio->estado);
        $this->assertSame('+524441234567', $negocio->telefono_whatsapp);
        $this->assertSame('Av. Carranza 100, Centro', $negocio->direccion);

        $dueno = $negocio->usuarios()->firstOrFail();
        $this->assertSame(Rol::Negocio, $dueno->rol);
        $this->assertSame('luis@tacosguero.mx', $dueno->email);
        $this->assertSame('activo', $dueno->estado);
        $this->assertTrue(Hash::check('secreta123', $dueno->password));

        // El dueño recibe todos los permisos de su negocio, registrados como dados por el administrador.
        foreach (ClavePermiso::delCatalogo(Rol::Negocio) as $clave) {
            $this->assertTrue($dueno->tienePermiso($clave, $negocio), $clave->value);
        }
        $this->assertDatabaseHas('usuario_permisos', ['usuario_id' => $dueno->id, 'asignado_por' => $this->admin->id]);
    }

    public function test_el_negocio_nuevo_abre_su_direccion_y_su_dueno_puede_entrar(): void
    {
        $this->enviar($this->datos());
        auth()->logout();

        $this->get('http://tacos-guero.'.config('tucarta.dominio'))->assertOk()->assertSee('Tacos El Güero');

        $this->post('http://auth.'.config('tucarta.dominio'), ['email' => 'luis@tacosguero.mx', 'password' => 'secreta123'])
            ->assertRedirect('http://tacos-guero.'.config('tucarta.dominio').'/panel');
    }

    public function test_el_negocio_nuevo_aparece_en_la_lista(): void
    {
        $this->enviar($this->datos());

        $this->actingAs($this->admin)->get(route('negocios.index'))->assertSee('Tacos El Güero')->assertSee('tacos-guero');
    }

    #[DataProvider('direccionesInvalidas')]
    public function test_cada_direccion_web_invalida_da_su_error_por_campo(string $direccion, string $error): void
    {
        Negocio::factory()->create(['subdominio' => 'repetida']);

        $this->enviar($this->datos(['direccion_web' => $direccion]))
            ->assertRedirect($this->url('/crear'))
            ->assertSessionHasErrors(['direccion_web' => $error]);

        $this->assertDatabaseMissing('usuarios', ['email' => 'luis@tacosguero.mx']);
    }

    public static function direccionesInvalidas(): array
    {
        return [
            'mayusculas' => ['TacosGuero', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'muy corta' => ['tg', 'La dirección web debe tener al menos 3 caracteres.'],
            'muy larga' => [str_repeat('t', 31), 'La dirección web no puede pasar de 30 caracteres.'],
            'caracteres raros' => ['tacos_güero!', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'repetida' => ['repetida', 'Esa dirección web ya está en uso.'],
            'reservada' => ['admin', 'Esa dirección web no está disponible.'],
            'reservada del servidor' => ['traefik', 'Esa dirección web no está disponible.'],
        ];
    }

    public function test_whatsapp_invalido_da_error(): void
    {
        $this->enviar($this->datos(['whatsapp' => '444 123 4567']))
            ->assertSessionHasErrors(['whatsapp' => 'Escribe el WhatsApp con lada y 10 dígitos, por ejemplo +524441234567.']);

        $this->assertDatabaseMissing('negocios', ['subdominio' => 'tacos-guero']);
    }

    public function test_los_campos_obligatorios_dan_error_cada_uno(): void
    {
        $this->enviar([])->assertSessionHasErrors([
            'nombre', 'direccion_web', 'whatsapp', 'ubicacion', 'nombre_dueno', 'apellidos_dueno', 'email', 'password',
        ])->assertSessionDoesntHaveErrors('descripcion');
    }

    public function test_un_correo_de_dueno_repetido_no_deja_negocio_nuevo(): void
    {
        Usuario::factory()->create(['email' => 'luis@tacosguero.mx']);
        $negociosAntes = Negocio::count();

        $this->enviar($this->datos())
            ->assertSessionHasErrors(['email' => 'Ya existe una cuenta con ese correo.']);

        $this->assertSame($negociosAntes, Negocio::count());
    }

    public function test_si_falla_la_creacion_del_dueno_no_queda_nada_a_medias(): void
    {
        // Falla la base al guardar al dueño, después de crear el negocio.
        Usuario::creating(fn () => throw new RuntimeException('falla simulada'));

        $this->withoutExceptionHandling();

        try {
            $this->enviar($this->datos());
            $this->fail('Se esperaba que fallara la creación del dueño.');
        } catch (RuntimeException $e) {
            $this->assertSame('falla simulada', $e->getMessage());
        }

        $this->assertDatabaseMissing('negocios', ['subdominio' => 'tacos-guero']);
        $this->assertSame(0, DB::table('usuario_permisos')->whereNotNull('negocio_id')->count());
    }

    public function test_el_alta_funciona_con_el_rol_real_de_la_aplicacion(): void
    {
        // Las peticiones web cambian a tucarta_app (sin superusuario ni BYPASSRLS).
        $rolDurante = null;
        Negocio::created(function () use (&$rolDurante) {
            $rolDurante = DB::selectOne('select current_user as rol')->rol;
        });

        $this->enviar($this->datos())->assertSessionHasNoErrors();

        $this->assertSame('tucarta_app', $rolDurante);
        $this->assertDatabaseHas('negocios', ['subdominio' => 'tacos-guero']);
    }

    public function test_un_moderador_con_permiso_sobre_un_solo_negocio_no_puede_dar_de_alta(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, Negocio::factory()->create());

        $this->actingAs($moderador)->get($this->url('/crear'))->assertForbidden();
        $this->enviar($this->datos(), $moderador)->assertForbidden();
        $this->assertDatabaseMissing('negocios', ['subdominio' => 'tacos-guero']);
    }

    public function test_un_moderador_sin_permisos_no_entra(): void
    {
        $moderador = Usuario::factory()->administrador()->create();

        $this->actingAs($moderador)->get($this->url('/crear'))->assertForbidden();
    }

    public function test_dueno_y_empleado_reciben_404(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($dueno)->get($this->url('/crear'))->assertNotFound();
        $this->actingAs($empleado)->post($this->url(), $this->datos())->assertNotFound();
        $this->assertDatabaseMissing('negocios', ['subdominio' => 'tacos-guero']);
    }

    public function test_un_visitante_sin_sesion_va_al_login(): void
    {
        $this->get($this->url('/crear'))->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }

    public function test_el_boton_de_alta_solo_lo_ve_quien_puede_dar_de_alta(): void
    {
        $this->actingAs($this->admin)->get(route('negocios.index'))->assertSee('Dar de alta un negocio');

        $moderador = Usuario::factory()->administrador()->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, Negocio::factory()->create());

        $this->actingAs($moderador)->get(route('negocios.index'))->assertDontSee('Dar de alta un negocio');
    }
}
