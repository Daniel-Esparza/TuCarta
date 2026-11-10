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
use Tests\TestCase;

class OpcionPrecioTest extends TestCase
{
    use RefreshDatabase;

    private function duenoConPermiso(): Usuario
    {
        $dueno = Usuario::factory()->deNegocio()->create();
        $this->seed(PermisosSeeder::class);
        $dueno->otorgarPermiso(ClavePermiso::EditarPerfil, $dueno->negocio);

        return $dueno;
    }

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

    private function opcion(GrupoOpcion $grupo): Opcion
    {
        return Opcion::create([
            'grupo_opcion_id' => $grupo->id,
            'negocio_id' => $grupo->negocio_id,
            'nombre' => 'Queso',
            'incremento_precio' => 15,
        ]);
    }

    private function urlOpcion(Negocio $negocio, GrupoOpcion $grupo): string
    {
        return route('opciones.store', [
            'subdominio' => $negocio->subdominio,
            'grupo' => $grupo,
        ]);
    }

    public function test_crea_opcion_con_incremento_precio(): void
    {
        $dueno = $this->duenoConPermiso();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post($this->urlOpcion($dueno->negocio, $grupo), ['nombre' => 'Queso', 'incremento_precio' => 15])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('opciones', [
            'nombre' => 'Queso',
            'incremento_precio' => 15,
        ]);
    }

    public function test_precio_cero_se_permite(): void
    {
        $dueno = $this->duenoConPermiso();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post($this->urlOpcion($dueno->negocio, $grupo), ['nombre' => 'Sin extra', 'incremento_precio' => 0])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('opciones', ['nombre' => 'Sin extra', 'incremento_precio' => 0]);
    }

    public function test_precio_negativo_da_error(): void
    {
        $dueno = $this->duenoConPermiso();
        $grupo = $this->crearGrupo($dueno->negocio);

        $this->actingAs($dueno)
            ->post($this->urlOpcion($dueno->negocio, $grupo), ['nombre' => 'Malo', 'incremento_precio' => -5])
            ->assertSessionHasErrors('incremento_precio');

        $this->assertDatabaseMissing('opciones', ['nombre' => 'Malo']);
    }

    public function test_la_pantalla_muestra_las_opciones_del_grupo(): void
    {
        $dueno = $this->duenoConPermiso();
        $grupo = $this->crearGrupo($dueno->negocio);
        $this->opcion($grupo);

        $url = route('grupos.show', ['subdominio' => $dueno->negocio->subdominio, 'grupo' => $grupo]);

        $this->actingAs($dueno)->get($url)
            ->assertOk()
            ->assertSee('Opciones de Extras')
            ->assertSee('Queso')
            ->assertSee('15.00');
    }

    public function test_edita_una_opcion(): void
    {
        $dueno = $this->duenoConPermiso();
        $opcion = $this->opcion($this->crearGrupo($dueno->negocio));

        $url = route('opciones.update', ['subdominio' => $dueno->negocio->subdominio, 'opcion' => $opcion]);

        $this->actingAs($dueno)
            ->put($url, ['nombre' => 'Queso doble', 'incremento_precio' => 25])
            ->assertRedirect();

        $this->assertDatabaseHas('opciones', ['id' => $opcion->id, 'nombre' => 'Queso doble', 'incremento_precio' => 25]);
    }

    public function test_elimina_una_opcion(): void
    {
        $dueno = $this->duenoConPermiso();
        $opcion = $this->opcion($this->crearGrupo($dueno->negocio));

        $url = route('opciones.destroy', ['subdominio' => $dueno->negocio->subdominio, 'opcion' => $opcion]);

        $this->actingAs($dueno)->delete($url)->assertRedirect();

        $this->assertDatabaseMissing('opciones', ['id' => $opcion->id]);
    }

    public function test_grupo_de_otro_negocio_responde_404(): void
    {
        $dueno = $this->duenoConPermiso();
        $otroNegocio = Negocio::factory()->create();
        $grupoAjeno = $this->crearGrupo($otroNegocio);

        $this->actingAs($dueno)
            ->post($this->urlOpcion($otroNegocio, $grupoAjeno), ['nombre' => 'X', 'incremento_precio' => 1])
            ->assertNotFound();

        $this->actingAs($dueno)
            ->post($this->urlOpcion($dueno->negocio, $grupoAjeno), ['nombre' => 'X', 'incremento_precio' => 1])
            ->assertNotFound();
    }

    public function test_un_empleado_sin_permiso_recibe_403(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $grupo = $this->crearGrupo($negocio);

        $this->actingAs($empleado)
            ->post($this->urlOpcion($negocio, $grupo), ['nombre' => 'X', 'incremento_precio' => 1])
            ->assertForbidden();
    }
}
