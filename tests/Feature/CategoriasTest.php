<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    private function duenoConPermiso(): array
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $dueno->permisos()->create([
            'permiso_id' => Permiso::firstOrCreate(
                ['clave' => 'editar_perfil'],
                ['descripcion' => 'Editar el perfil del negocio', 'tipo_usuario' => 'negocio']
            )->id,
            'negocio_id' => $negocio->id,
        ]);

        return [$negocio, $dueno];
    }

    private function categoria(Negocio $negocio, string $nombre, int $orden = 0): Categoria
    {
        return Categoria::create([
            'negocio_id' => $negocio->id,
            'nombre' => $nombre,
            'orden' => $orden,
        ]);
    }

    private function producto(Categoria $categoria): Item
    {
        return Item::create([
            'categoria_id' => $categoria->id,
            'negocio_id' => $categoria->negocio_id,
            'nombre' => 'Taco',
            'precio' => 30,
        ]);
    }

    public function test_el_dueno_crea_una_categoria_al_final_de_su_lista(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();

        $this->actingAs($dueno)
            ->post(route('categorias.store'), ['nombre' => 'Entradas'])
            ->assertRedirect(route('categorias.index'));

        $this->actingAs($dueno)
            ->post(route('categorias.store'), ['nombre' => 'Bebidas']);

        $this->assertDatabaseHas('categorias', [
            'negocio_id' => $negocio->id,
            'nombre' => 'Entradas',
            'orden' => 0,
        ]);

        $this->assertDatabaseHas('categorias', [
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
            'orden' => 1,
        ]);
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        [, $dueno] = $this->duenoConPermiso();

        $this->actingAs($dueno)
            ->post(route('categorias.store'), [])
            ->assertSessionHasErrors('nombre');
    }

    public function test_no_se_repite_el_nombre_en_el_mismo_negocio_sin_importar_mayusculas(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $this->categoria($negocio, 'Bebidas');

        $this->actingAs($dueno)
            ->post(route('categorias.store'), ['nombre' => 'bebidas'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Categoria::where('negocio_id', $negocio->id)->count());
    }

    public function test_el_mismo_nombre_se_permite_en_otro_negocio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $otro = Negocio::factory()->create();
        $this->categoria($otro, 'Bebidas');

        $this->actingAs($dueno)
            ->post(route('categorias.store'), ['nombre' => 'Bebidas'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categorias', [
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);
    }

    public function test_el_dueno_edita_el_nombre_y_puede_guardar_el_mismo_sin_error(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio, 'Entradas');

        $this->actingAs($dueno)
            ->put(route('categorias.update', $categoria), ['nombre' => 'Entradas'])
            ->assertSessionHasNoErrors();

        $this->actingAs($dueno)
            ->put(route('categorias.update', $categoria), ['nombre' => 'Para empezar'])
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => 'Para empezar',
        ]);
    }

    public function test_editar_con_un_nombre_repetido_da_error(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $this->categoria($negocio, 'Bebidas', 0);
        $entradas = $this->categoria($negocio, 'Entradas', 1);

        $this->actingAs($dueno)
            ->put(route('categorias.update', $entradas), ['nombre' => 'Bebidas'])
            ->assertSessionHasErrors('nombre', null, 'editar');
    }

    public function test_el_dueno_elimina_una_categoria_vacia(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio, 'Postres');

        $this->actingAs($dueno)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_no_se_elimina_una_categoria_con_productos(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio, 'Tacos');
        $this->producto($categoria);

        $this->actingAs($dueno)
            ->delete(route('categorias.destroy', $categoria))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
        ]);
    }

    public function test_subir_y_bajar_cambian_el_orden(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $a = $this->categoria($negocio, 'A', 0);
        $b = $this->categoria($negocio, 'B', 1);
        $c = $this->categoria($negocio, 'C', 2);

        $this->actingAs($dueno)->patch(route('categorias.subir', $b));
        $this->assertSame(['B', 'A', 'C'], Categoria::where('negocio_id', $negocio->id)->orderBy('orden')->pluck('nombre')->all());

        $this->actingAs($dueno)->patch(route('categorias.bajar', $a));
        $this->assertSame(['B', 'C', 'A'], Categoria::where('negocio_id', $negocio->id)->orderBy('orden')->pluck('nombre')->all());

        $this->actingAs($dueno)->patch(route('categorias.subir', $b));
        $this->assertSame(['B', 'C', 'A'], Categoria::where('negocio_id', $negocio->id)->orderBy('orden')->pluck('nombre')->all());
    }

    public function test_las_categorias_vacias_no_pasan_el_filtro_del_menu_publico(): void
    {
        [$negocio] = $this->duenoConPermiso();
        $llena = $this->categoria($negocio, 'Tacos', 0);
        $this->categoria($negocio, 'Vacía', 1);
        $this->producto($llena);

        $nombres = Categoria::where('negocio_id', $negocio->id)->conProductos()->pluck('nombre')->all();
        $this->assertSame(['Tacos'], $nombres);
    }

    public function test_la_pantalla_solo_muestra_las_categorias_del_propio_negocio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $this->categoria($negocio, 'MiCategoria');
        $this->categoria(Negocio::factory()->create(), 'CategoriaAjena');

        $this->actingAs($dueno)
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('MiCategoria')
            ->assertDontSee('CategoriaAjena');
    }

    public function test_un_dueno_no_toca_categorias_de_otro_negocio(): void
    {
        [, $dueno] = $this->duenoConPermiso();
        $ajena = $this->categoria(Negocio::factory()->create(), 'Ajena');

        $this->actingAs($dueno)->put(route('categorias.update', $ajena), ['nombre' => 'Hackeada'])->assertNotFound();
        $this->actingAs($dueno)->patch(route('categorias.subir', $ajena))->assertNotFound();
        $this->actingAs($dueno)->delete(route('categorias.destroy', $ajena))->assertNotFound();

        $this->assertDatabaseHas('categorias', [
            'id' => $ajena->id,
            'nombre' => 'Ajena',
        ]);
    }

    public function test_un_empleado_sin_permiso_o_un_visitante_no_entran(): void
    {
        [$negocio] = $this->duenoConPermiso();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado)->get(route('categorias.index'))->assertForbidden();
        $this->actingAs($empleado)->post(route('categorias.store'), ['nombre' => 'X'])->assertForbidden();
    }
}