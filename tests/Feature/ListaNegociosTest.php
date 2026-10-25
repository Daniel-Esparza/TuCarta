<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListaNegociosTest extends TestCase
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

    public function test_el_administrador_con_permiso_global_ve_todos_los_negocios_en_orden(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin, 'gestionar_negocios');

        Negocio::factory()->create(['nombre' => 'Antojitos La Esquina']);
        Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);

        $this->actingAs($admin)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSeeInOrder(['Antojitos La Esquina', 'Tacos Zafiro']);
    }

    public function test_el_moderador_con_permiso_sobre_un_negocio_solo_ve_ese(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $tacos = Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);
        Negocio::factory()->create(['nombre' => 'Sushi Maru']);
        $this->darPermiso($moderador, 'gestionar_negocios', $tacos);

        $this->actingAs($moderador)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSee('Tacos Zafiro')
            ->assertDontSee('Sushi Maru');
    }

    public function test_el_moderador_sin_permisos_recibe_403(): void
    {
        $moderador = Usuario::factory()->administrador()->create();

        $this->actingAs($moderador)
            ->get(route('negocios.index'))
            ->assertForbidden();
    }

    public function test_el_dueno_de_un_negocio_recibe_403(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->create([
            'rol' => Rol::Negocio,
            'negocio_id' => $negocio->id,
        ]);

        $this->actingAs($dueno)
            ->get(route('negocios.index'))
            ->assertForbidden();
    }

    public function test_la_tabla_muestra_el_estado_de_cada_negocio(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin, 'gestionar_negocios');

        Negocio::factory()->create(['nombre' => 'Cafe Luna', 'estado' => 'activo']);
        Negocio::factory()->pendiente()->create(['nombre' => 'Marisqueria El Puerto']);

        $this->actingAs($admin)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSee('Activo')
            ->assertSee('Pendiente');
    }

    public function test_sin_negocios_se_muestra_el_mensaje_de_vacio(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin, 'gestionar_negocios');

        $this->actingAs($admin)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSee('Todavía no hay negocios.');
    }

}