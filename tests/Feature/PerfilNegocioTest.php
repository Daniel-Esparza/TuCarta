<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function darPermiso(Usuario $usuario, string $clave, Negocio $negocio): void
    {
        $permiso = Permiso::firstOrCreate(
            ['clave' => $clave],
            ['descripcion' => $clave, 'tipo_usuario' => 'negocio']
        );

        UsuarioPermiso::firstOrCreate([
            'usuario_id' => $usuario->id,
            'permiso_id' => $permiso->id,
            'negocio_id' => $negocio->id,
        ]);
    }

    private function dueno(Negocio $negocio): Usuario
    {
        return Usuario::factory()->deNegocio($negocio)->create();
    }

    private function urlPublica(Negocio $negocio): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/';
    }

    private function datosValidos(array $extras = []): array
    {
        return array_merge([
            'nombre' => 'Tacos Juan Reforma',
            'descripcion' => 'Tacos al pastor de la casa',
            'direccion' => 'Av. Reforma 100, Centro',
            'telefono_whatsapp' => '+524441234567',
        ], $extras);
    }

    public function test_el_dueno_edita_y_los_datos_quedan_guardados(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Tacos Juan', 'subdominio' => 'tacos-juan']);
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        
        $this->actingAs($dueno)->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('Tacos Juan')
            ->assertSee('tacos-juan')
            ->assertSee('solo el administrador puede cambiarla');

        
        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos(['telefono_whatsapp' => '+52 444-123-4567']))
            ->assertRedirect(route('perfil.edit'));

        $negocio = $negocio->fresh();
        $this->assertSame('Tacos Juan Reforma', $negocio->nombre);
        $this->assertSame('Tacos al pastor de la casa', $negocio->descripcion);
        $this->assertSame('Av. Reforma 100, Centro', $negocio->direccion);
        $this->assertSame('+524441234567', $negocio->telefono_whatsapp);
    }

    public function test_un_whatsapp_invalido_da_error_por_campo(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos(['telefono_whatsapp' => '4441234567']))
            ->assertRedirect()
            ->assertSessionHasErrors('telefono_whatsapp');

        $this->assertSame($negocio->telefono_whatsapp, $negocio->fresh()->telefono_whatsapp);
    }

    public function test_el_dueno_no_puede_cambiar_la_direccion_web_aunque_la_mande(): void
    {
        $negocio = Negocio::factory()->create(['subdominio' => 'tacos-juan']);
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos(['subdominio' => 'otra-direccion']))
            ->assertRedirect(route('perfil.edit'));

        $this->assertSame('tacos-juan', $negocio->fresh()->subdominio);
    }

    public function test_un_dueno_no_puede_editar_el_perfil_de_otro_negocio(): void
    {
        $tacos = Negocio::factory()->create(['nombre' => 'Tacos Juan', 'subdominio' => 'tacos-juan']);
        $sushi = Negocio::factory()->create(['nombre' => 'Sushi Ana', 'subdominio' => 'sushi-ana']);
        $dueno = $this->dueno($tacos);
        $this->darPermiso($dueno, 'editar_perfil', $tacos);

        
        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos([
                'negocio_id' => $sushi->id,
                'subdominio' => 'sushi-ana',
            ]))
            ->assertRedirect(route('perfil.edit'));

        
        $this->assertSame('Sushi Ana', $sushi->fresh()->nombre);
        $this->assertSame('sushi-ana', $sushi->fresh()->subdominio);
        $this->assertSame('Tacos Juan Reforma', $tacos->fresh()->nombre);
        $this->assertSame('tacos-juan', $tacos->fresh()->subdominio);
    }

    public function test_un_empleado_sin_permiso_no_entra(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $this->darPermiso($empleado, 'avanzar_pedidos', $negocio); // tiene otro permiso, no editar_perfil

        $this->actingAs($empleado)->get(route('perfil.edit'))->assertForbidden();
        $this->actingAs($empleado)->patch(route('perfil.update'), $this->datosValidos())->assertForbidden();
    }

    public function test_un_empleado_con_permiso_edita_el_perfil(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $this->darPermiso($empleado, 'editar_perfil', $negocio);

        $this->actingAs($empleado)
            ->patch(route('perfil.update'), $this->datosValidos())
            ->assertRedirect(route('perfil.edit'));

        $this->assertSame('Tacos Juan Reforma', $negocio->fresh()->nombre);
    }

    public function test_el_logo_se_sube_y_queda_guardado(): void
    {
        Storage::fake('public');
        $negocio = Negocio::factory()->create();
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos([
                'logo' => UploadedFile::fake()->image('logo.png', 120, 120),
            ]))
            ->assertRedirect(route('perfil.edit'));

        $negocio = $negocio->fresh();
        $this->assertNotNull($negocio->logo_path);
        Storage::disk('public')->assertExists($negocio->logo_path);
    }

    public function test_un_logo_con_tipo_no_permitido_da_error(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos([
                'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('logo');

        $this->assertNull($negocio->fresh()->logo_path);
    }

    public function test_un_logo_demasiado_grande_da_error(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos([
                'logo' => UploadedFile::fake()->image('logo.png')->size(3000),
            ]))
            ->assertSessionHasErrors('logo');

        $this->assertNull($negocio->fresh()->logo_path);
    }

    public function test_el_cambio_aparece_en_la_pagina_publica(): void
    {
        $negocio = Negocio::factory()->create([
            'nombre' => 'Tacos Juan',
            'descripcion' => 'Comida rapida',
            'subdominio' => 'tacos-juan',
            'estado' => 'activo',
        ]);
        $dueno = $this->dueno($negocio);
        $this->darPermiso($dueno, 'editar_perfil', $negocio);

        $this->actingAs($dueno)
            ->patch(route('perfil.update'), $this->datosValidos())
            ->assertRedirect(route('perfil.edit'));

        $this->get($this->urlPublica($negocio))
            ->assertOk()
            ->assertSee('Tacos Juan Reforma')
            ->assertSee('Tacos al pastor de la casa')
            ->assertSee('Av. Reforma 100, Centro')
            ->assertSee('+524441234567')
            ->assertDontSee('Comida rapida');
    }

    public function test_un_invitado_recibe_403(): void
    {
        $this->get(route('perfil.edit'))->assertForbidden();
        $this->patch(route('perfil.update'), $this->datosValidos())->assertForbidden();
    }
}