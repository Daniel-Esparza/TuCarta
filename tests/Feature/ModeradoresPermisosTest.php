<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeradoresPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function darPermiso(Usuario $usuario, string $clave, ?Negocio $negocio = null): UsuarioPermiso
    {
        $permiso = Permiso::firstOrCreate(
            ['clave' => $clave],
            ['descripcion' => $clave, 'tipo_usuario' => 'administrador']
        );

        return UsuarioPermiso::create([
            'usuario_id' => $usuario->id,
            'permiso_id' => $permiso->id,
            'negocio_id' => $negocio?->id,
        ]);
    }

    public function test_moderador_sin_permisos_no_ve_ningun_negocio(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $negocio = Negocio::factory()->create();

        $this->assertFalse($moderador->can('viewAny', Negocio::class));
        $this->assertFalse($moderador->can('view', $negocio));
    }

    public function test_moderador_con_permiso_ve_solo_ese_negocio_y_lo_pierde_al_quitarlo(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $tacos = Negocio::factory()->create();
        $sushi = Negocio::factory()->create();

        $asignacion = $this->darPermiso($moderador, 'gestionar_negocios', $tacos);

        $this->assertTrue($moderador->can('view', $tacos));
        $this->assertFalse($moderador->can('view', $sushi));

        // Sin volver a iniciar sesión: se consulta la base en cada llamada.
        $asignacion->delete();

        $this->assertFalse($moderador->can('view', $tacos));
    }

    public function test_moderador_no_puede_tocar_cuentas_de_administrador(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $this->darPermiso($moderador, 'gestionar_negocios'); // global, pero sin gestionar_moderadores
        $otroAdmin = Usuario::factory()->administrador()->create();

        $this->assertFalse($moderador->can('create', Usuario::class));
        $this->assertFalse($moderador->can('update', $otroAdmin));
        $this->assertFalse($moderador->can('delete', $otroAdmin));

        $principal = Usuario::factory()->administrador()->create();
        $this->darPermiso($principal, 'gestionar_moderadores');

        $this->assertTrue($principal->can('create', Usuario::class));
        $this->assertTrue($principal->can('update', $moderador));
    }

        public function test_administrador_principal_crea_moderador_sin_permisos(): void
    {
        $principal = Usuario::factory()->administrador()->create();
        $this->darPermiso($principal, 'gestionar_moderadores');

        $this->actingAs($principal)->post(route('moderadores.store'), [
            'nombre' => 'Mod',
            'apellidos' => 'Prueba',
            'email' => 'mod-prueba@tucarta.local',
            'telefono_whatsapp' => '4440000000',
            'password' => 'password123',
        ])->assertRedirect();

        $nuevo = Usuario::where('email', 'mod-prueba@tucarta.local')->first();
        $this->assertNotNull($nuevo);
        $this->assertTrue($nuevo->esAdministrador());
        $this->assertSame(0, $nuevo->permisos()->count());
    }

    public function test_moderador_recibe_403_al_intentar_crear_moderadores(): void
    {
        $moderador = Usuario::factory()->administrador()->create();

        $this->actingAs($moderador)->post(route('moderadores.store'), [
            'nombre' => 'X',
            'apellidos' => 'Y',
            'email' => 'x@tucarta.local',
            'telefono_whatsapp' => '4440000000',
            'password' => 'password123',
        ])->assertForbidden();
    }
}