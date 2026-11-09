<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Opcion;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GruposOpcionesTest extends TestCase
{
    use RefreshDatabase;

    private function duenoConPermiso(): array
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $this->seed(PermisosSeeder::class);
        $dueno->otorgarPermiso(ClavePermiso::EditarPerfil, $negocio);

        URL::defaults(['subdominio' => $negocio->subdominio]);

        return [$negocio, $dueno];
    }

    private function producto(Negocio $negocio, string $nombre = 'Taco'): Item
    {
        $categoria = Categoria::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Tacos',
            'orden' => 0,
        ]);

        return Item::create([
            'categoria_id' => $categoria->id,
            'negocio_id' => $negocio->id,
            'nombre' => $nombre,
            'precio' => 30,
        ]);
    }

    private function grupo(Item $item, array $extra = []): GrupoOpcion
    {
        return GrupoOpcion::create($extra + [
            'item_id' => $item->id,
            'negocio_id' => $item->negocio_id,
            'nombre' => 'Tamaño',
            'es_multiple' => false,
            'es_obligatorio' => false,
            'min_selecciones' => 0,
            'max_selecciones' => 1,
            'orden' => 0,
        ]);
    }

    public function test_un_grupo_de_seleccion_unica_fuerza_maximo_uno(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);

        $this->actingAs($dueno)
            ->post(route('grupos.store', $item), [
                'nombre' => 'Tamaño',
                'min_selecciones' => 0,
                'max_selecciones' => 5,
            ])
            ->assertRedirect(route('grupos.index', $item));

        $this->assertDatabaseHas('grupos_opciones', [
            'item_id' => $item->id,
            'nombre' => 'Tamaño',
            'es_multiple' => false,
            'max_selecciones' => 1,
        ]);
    }

    public function test_un_grupo_obligatorio_exige_al_menos_una_opcion(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);

        $this->actingAs($dueno)->post(route('grupos.store', $item), [
            'nombre' => 'Extras',
            'es_multiple' => 1,
            'es_obligatorio' => 1,
            'min_selecciones' => 0,
            'max_selecciones' => 3,
        ]);

        $this->assertDatabaseHas('grupos_opciones', [
            'nombre' => 'Extras',
            'es_multiple' => true,
            'es_obligatorio' => true,
            'min_selecciones' => 1,
            'max_selecciones' => 3,
        ]);
    }

    public function test_el_minimo_no_puede_pasar_del_maximo(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);

        $this->actingAs($dueno)->post(route('grupos.store', $item), [
            'nombre' => 'Salsas',
            'es_multiple' => 1,
            'min_selecciones' => 3,
            'max_selecciones' => 2,
        ])->assertSessionHasErrors('min_selecciones');

        $this->assertDatabaseMissing('grupos_opciones', ['nombre' => 'Salsas']);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);

        $this->actingAs($dueno)
            ->post(route('grupos.store', $item), [])
            ->assertSessionHasErrors('nombre');
    }

    public function test_el_dueno_edita_un_grupo(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);
        $grupo = $this->grupo($item);

        $this->actingAs($dueno)->put(route('grupos.update', [$item, $grupo]), [
            'nombre' => 'Extras',
            'es_multiple' => 1,
            'min_selecciones' => 0,
            'max_selecciones' => 2,
        ])->assertRedirect(route('grupos.index', $item));

        $this->assertDatabaseHas('grupos_opciones', [
            'id' => $grupo->id,
            'nombre' => 'Extras',
            'es_multiple' => true,
            'max_selecciones' => 2,
        ]);
    }

    public function test_eliminar_un_grupo_borra_sus_opciones_y_no_el_producto(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);
        $grupo = $this->grupo($item);
        $opcion = Opcion::create([
            'grupo_opcion_id' => $grupo->id,
            'negocio_id' => $negocio->id,
            'nombre' => 'Chico',
            'incremento_precio' => 0,
        ]);

        $this->actingAs($dueno)
            ->delete(route('grupos.destroy', [$item, $grupo]))
            ->assertRedirect(route('grupos.index', $item));

        $this->assertDatabaseMissing('grupos_opciones', ['id' => $grupo->id]);
        $this->assertDatabaseMissing('opciones', ['id' => $opcion->id]);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'nombre' => 'Taco']);
    }

    public function test_un_producto_sin_grupos_se_muestra_como_siempre(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $item = $this->producto($negocio);

        $this->actingAs($dueno)
            ->get(route('grupos.index', $item))
            ->assertOk()
            ->assertSee('Este producto no tiene grupos de opciones');
    }

    public function test_un_producto_de_otro_negocio_responde_404(): void
    {
        [, $dueno] = $this->duenoConPermiso();
        $ajeno = $this->producto(Negocio::factory()->create(), 'Ajeno');
        $grupoAjeno = $this->grupo($ajeno);

        $this->actingAs($dueno)->get(route('grupos.index', $ajeno))->assertNotFound();
        $this->actingAs($dueno)->post(route('grupos.store', $ajeno), [
            'nombre' => 'Hackeado', 'min_selecciones' => 0, 'max_selecciones' => 1,
        ])->assertNotFound();
        $this->actingAs($dueno)->delete(route('grupos.destroy', [$ajeno, $grupoAjeno]))->assertNotFound();

        $this->assertDatabaseHas('grupos_opciones', ['id' => $grupoAjeno->id]);
    }

    public function test_un_grupo_no_se_puede_usar_desde_otro_producto(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $primero = $this->producto($negocio, 'Taco');
        $segundo = $this->producto($negocio, 'Quesadilla');
        $grupo = $this->grupo($primero);

        $this->actingAs($dueno)
            ->delete(route('grupos.destroy', [$segundo, $grupo]))
            ->assertNotFound();

        $this->assertDatabaseHas('grupos_opciones', ['id' => $grupo->id]);
    }

    public function test_un_empleado_sin_permiso_recibe_403(): void
    {
        [$negocio] = $this->duenoConPermiso();
        $item = $this->producto($negocio);
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado)->get(route('grupos.index', $item))->assertForbidden();
        $this->actingAs($empleado)->post(route('grupos.store', $item), [
            'nombre' => 'X', 'min_selecciones' => 0, 'max_selecciones' => 1,
        ])->assertForbidden();
    }
}