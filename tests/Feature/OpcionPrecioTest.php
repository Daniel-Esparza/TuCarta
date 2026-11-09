<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpcionPrecioTest extends TestCase
{
    use RefreshDatabase;

    private function crearGrupo(Negocio $negocio): GrupoOpcion
    {
        $categoria = Categoria::create([
            'nombre' => 'Comidas',
            'negocio_id' => $negocio->id,
        ]);

        $item = Item::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Hamburguesa',
            'precio' => 100,
            'activo' => true,
            'disponible_hoy' => true,
            'negocio_id' => $negocio->id,
        ]);

        return GrupoOpcion::create([
            'item_id' => $item->id,
            'nombre' => 'Extras',
            'es_multiple' => true,
            'es_obligatorio' => false,
            'min_selecciones' => 0,
            'max_selecciones' => 3,
            'orden' => 1,
            'negocio_id' => $negocio->id,
        ]);
    }

    public function test_crea_opcion_con_incremento_precio(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post(route('opciones.store', $grupo), ['nombre' => 'Queso', 'incremento_precio' => 15])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('opciones', [
            'nombre' => 'Queso',
            'incremento_precio' => 15,
        ]);
    }

    public function test_precio_cero_se_permite(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post(route('opciones.store', $grupo), ['nombre' => 'Sin extra', 'incremento_precio' => 0])
            ->assertSessionHasNoErrors();
    }

    public function test_precio_negativo_da_error(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post(route('opciones.store', $grupo), ['nombre' => 'Malo', 'incremento_precio' => -5])
            ->assertSessionHasErrors('incremento_precio');

        $this->assertDatabaseMissing('opciones', ['nombre' => 'Malo']);
    }

    public function test_grupo_de_otro_negocio_responde_404(): void
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $otroNegocio = Negocio::factory()->create();
        $grupoAjeno = $this->crearGrupo($otroNegocio);

        $this->actingAs($dueno)
            ->post(route('opciones.store', $grupoAjeno), ['nombre' => 'X', 'incremento_precio' => 1])
            ->assertNotFound();
    }
}
