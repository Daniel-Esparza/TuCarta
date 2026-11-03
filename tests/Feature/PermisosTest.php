<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
    }

    public function test_el_catalogo_tiene_las_claves_separadas_por_tipo(): void
    {
        $this->assertSame(9, Permiso::count());

        $deNegocio = Permiso::where('tipo_usuario', Rol::Negocio->value)->orderBy('clave')->pluck('clave')->all();
        $deAdministrador = Permiso::where('tipo_usuario', Rol::Administrador->value)->orderBy('clave')->pluck('clave')->all();

        $this->assertSame(
            ['avanzar_pedidos', 'editar_perfil', 'editar_precios', 'gestionar_equipo', 'marcar_agotado', 'ver_metricas', 'ver_pedidos'],
            $deNegocio
        );
        $this->assertSame(['gestionar_moderadores', 'gestionar_negocios'], $deAdministrador);
    }

    public function test_correr_el_seeder_dos_veces_no_duplica_el_catalogo(): void
    {
        $this->seed(PermisosSeeder::class);

        $this->assertSame(9, Permiso::count());
    }

    public function test_el_dueno_tiene_todos_los_permisos_de_su_negocio(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $dueno->otorgarPermisosDeDueno();

        foreach (ClavePermiso::delCatalogo(Rol::Negocio) as $clave) {
            $this->assertTrue($dueno->tienePermiso($clave), "Falta {$clave->value}");
        }
    }

    public function test_los_permisos_del_dueno_no_aplican_en_otro_negocio(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $dueno->otorgarPermisosDeDueno();

        $this->assertFalse($dueno->tienePermiso(ClavePermiso::VerPedidos, Negocio::factory()->create()));
    }

    public function test_un_permiso_de_un_negocio_solo_aplica_a_ese_negocio(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        [$permitido, $otro] = Negocio::factory()->count(2)->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, $permitido);

        $this->assertTrue($moderador->tienePermiso(ClavePermiso::GestionarNegocios, $permitido));
        $this->assertTrue($moderador->tienePermiso(ClavePermiso::GestionarNegocios, $permitido->id));
        $this->assertFalse($moderador->tienePermiso(ClavePermiso::GestionarNegocios, $otro));
        $this->assertSame([$permitido->id], $moderador->negociosConPermiso(ClavePermiso::GestionarNegocios)->all());
        $this->assertFalse($moderador->tienePermisoGlobal(ClavePermiso::GestionarNegocios));
    }

    public function test_una_cuenta_de_negocio_no_puede_recibir_permisos_de_administrador(): void
    {
        $empleado = Usuario::factory()->deNegocio()->create();

        $this->expectException(InvalidArgumentException::class);
        $empleado->otorgarPermiso(ClavePermiso::GestionarNegocios);
    }

    public function test_un_administrador_no_puede_recibir_permisos_de_negocio(): void
    {
        $moderador = Usuario::factory()->administrador()->create();

        $this->expectException(InvalidArgumentException::class);
        $moderador->otorgarPermiso(ClavePermiso::VerPedidos, Negocio::factory()->create());
    }

    public function test_otorgar_dos_veces_el_mismo_permiso_no_lo_duplica(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios);
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios);

        $this->assertSame(1, $moderador->permisos()->count());
    }

    public function test_quitar_un_permiso_se_refleja_de_inmediato(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $negocio = Negocio::factory()->create();
        $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, $negocio);

        $moderador->quitarPermiso(ClavePermiso::GestionarNegocios, $negocio);

        $this->assertFalse($moderador->tienePermiso(ClavePermiso::GestionarNegocios, $negocio));
    }

    public function test_se_guarda_quien_otorgo_el_permiso(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $moderador = Usuario::factory()->administrador()->create();

        $asignacion = $moderador->otorgarPermiso(ClavePermiso::GestionarNegocios, null, $admin);

        $this->assertSame($admin->id, $asignacion->asignado_por);
    }

    public function test_los_datos_de_prueba_reparten_los_permisos_del_sprint_5(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::where('email', 'admin@tucarta.local')->first();
        $moderador = Usuario::where('email', 'moderador@tucarta.local')->first();
        $juan = Usuario::where('email', 'juan@tacos-juan.tucarta.local')->first();
        $marco = Usuario::where('email', 'marco@tacos-juan.tucarta.local')->first();
        $sushi = Negocio::where('subdominio', 'sushi-ana')->first();

        $this->assertTrue($admin->tienePermisoGlobal(ClavePermiso::GestionarNegocios));
        $this->assertTrue($admin->tienePermisoGlobal(ClavePermiso::GestionarModeradores));
        $this->assertSame(0, $moderador->permisos()->count());

        $this->assertTrue($juan->tienePermiso(ClavePermiso::GestionarEquipo));
        $this->assertTrue($juan->tienePermiso(ClavePermiso::VerMetricas));
        $this->assertFalse($juan->tienePermiso(ClavePermiso::VerMetricas, $sushi));

        $this->assertTrue($marco->tienePermiso(ClavePermiso::AvanzarPedidos));
        $this->assertFalse($marco->tienePermiso(ClavePermiso::VerMetricas));
        $this->assertSame(9, Permiso::count());
    }
}
