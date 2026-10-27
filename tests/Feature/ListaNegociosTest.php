<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListaNegociosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
    }

    private function darPermiso(Usuario $usuario, ?Negocio $negocio = null): void
    {
        $usuario->otorgarPermiso(ClavePermiso::GestionarNegocios, $negocio);
    }

    private function crearItems(Negocio $negocio, int $cantidad): void
    {
        $categoria = $negocio->categorias()->create(['nombre' => 'Platillos', 'orden' => 1]);

        foreach (range(1, $cantidad) as $i) {
            $categoria->items()->create([
                'negocio_id' => $negocio->id,
                'nombre' => "Platillo $i",
                'descripcion' => 'De prueba',
                'precio' => 50.00,
            ]);
        }
    }

    public function test_el_administrador_con_permiso_global_ve_todos_los_negocios_en_orden(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);
        Negocio::factory()->create(['nombre' => 'Antojitos La Esquina']);

        $this->actingAs($admin)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSeeInOrder(['Antojitos La Esquina', 'Tacos Zafiro']);
    }

    public function test_la_tabla_muestra_los_cinco_datos_de_cada_negocio(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        $negocio = Negocio::factory()->create(['nombre' => 'Tacos Zafiro', 'subdominio' => 'tacos-zafiro']);
        $this->crearItems($negocio, 12);

        $this->actingAs($admin)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Tacos Zafiro',
                'tacos-zafiro',
                'Activo',
                '12',
                $negocio->fresh()->created_at->format('d/m/Y'),
            ]);
    }

    public function test_la_busqueda_por_nombre_regresa_lo_correcto(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        Negocio::factory()->create(['nombre' => 'Tacos Zafiro', 'subdominio' => 'zafiro']);
        Negocio::factory()->create(['nombre' => 'Sushi Maru', 'subdominio' => 'maru']);

        $this->actingAs($admin)
            ->get(route('negocios.index', ['q' => 'zafiro']))
            ->assertOk()
            ->assertSee('Tacos Zafiro')
            ->assertDontSee('Sushi Maru');
    }

    public function test_la_busqueda_por_direccion_web_regresa_lo_correcto(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        Negocio::factory()->create(['nombre' => 'La Fogata', 'subdominio' => 'fogata-abc123']);
        Negocio::factory()->create(['nombre' => 'El Corral', 'subdominio' => 'corral']);

        $this->actingAs($admin)
            ->get(route('negocios.index', ['q' => 'abc123']))
            ->assertOk()
            ->assertSee('La Fogata')
            ->assertDontSee('El Corral');
    }

    public function test_el_filtro_por_estado_regresa_lo_correcto(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        Negocio::factory()->create(['nombre' => 'Antojitos Pancha', 'estado' => 'activo']);
        Negocio::factory()->pendiente()->create(['nombre' => 'Marisqueria Puerto']);
        Negocio::factory()->create(['nombre' => 'Cafe Luna', 'estado' => 'suspendido']);

        $this->actingAs($admin)
            ->get(route('negocios.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertSee('Marisqueria Puerto')
            ->assertDontSee('Antojitos Pancha')
            ->assertDontSee('Cafe Luna');
    }

    public function test_con_21_negocios_la_primera_pagina_muestra_20_y_existe_la_segunda(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        foreach (range(1, 21) as $i) {
            Negocio::factory()->create(['nombre' => 'Negocio '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $primera = $this->actingAs($admin)->get(route('negocios.index'));
        $primera->assertOk()
            ->assertSee('Negocio 01')
            ->assertDontSee('Negocio 21');

        $this->actingAs($admin)
            ->get(route('negocios.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Negocio 21');
    }

    public function test_suspender_cambia_el_estado_y_deja_intactos_los_productos(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        $negocio = Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);
        $this->crearItems($negocio, 3);

        $this->actingAs($admin)
            ->patch(route('negocios.suspender', $negocio))
            ->assertRedirect(route('negocios.index'));

        $this->assertSame('suspendido', $negocio->fresh()->estado);
        $this->assertSame(3, $negocio->fresh()->items()->count());
    }

    public function test_reactivar_lo_regresa_a_activo(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        $negocio = Negocio::factory()->create(['estado' => 'suspendido']);

        $this->actingAs($admin)
            ->patch(route('negocios.reactivar', $negocio))
            ->assertRedirect(route('negocios.index'));

        $this->assertSame('activo', $negocio->fresh()->estado);
    }

    public function test_una_peticion_get_a_suspender_no_cambia_nada(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->darPermiso($admin);

        $negocio = Negocio::factory()->create();

        $this->actingAs($admin)
            ->get(route('negocios.suspender', $negocio))
            ->assertStatus(405);

        $this->assertSame('activo', $negocio->fresh()->estado);
    }

    public function test_el_moderador_con_permiso_sobre_un_negocio_solo_ve_ese(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $tacos = Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);
        Negocio::factory()->create(['nombre' => 'Sushi Maru']);
        $this->darPermiso($moderador, $tacos);

        $this->actingAs($moderador)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSee('Tacos Zafiro')
            ->assertDontSee('Sushi Maru');
    }

    public function test_el_moderador_sin_permisos_ve_la_lista_vacia(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        Negocio::factory()->create(['nombre' => 'Tacos Zafiro']);

        $this->actingAs($moderador)
            ->get(route('negocios.index'))
            ->assertOk()
            ->assertSee('No se encontraron negocios.')
            ->assertDontSee('Tacos Zafiro');
    }

    public function test_el_dueno_y_el_empleado_no_entran(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->create(['rol' => Rol::Negocio, 'negocio_id' => $negocio->id]);
        $empleado = Usuario::factory()->create(['rol' => Rol::Negocio, 'negocio_id' => $negocio->id]);

        // Como en todo el panel central, una cuenta de negocio recibe 404 y no 403.
        $this->actingAs($dueno)->get(route('negocios.index'))->assertNotFound();
        $this->actingAs($empleado)->get(route('negocios.index'))->assertNotFound();
    }

    public function test_un_invitado_sin_sesion_va_al_login(): void
    {
        $this->get('http://auth.'.config('tucarta.dominio').'/panel/negocios')->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }

    public function test_un_moderador_sin_permisos_no_puede_suspender(): void
    {
        $moderador = Usuario::factory()->administrador()->create();
        $negocio = Negocio::factory()->create();

        $this->actingAs($moderador)
            ->patch(route('negocios.suspender', $negocio))
            ->assertForbidden();

        $this->assertSame('activo', $negocio->fresh()->estado);
    }
}