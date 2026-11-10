<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoDisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private function producto(Negocio $negocio): Item
    {
        $categoria = Categoria::create(['nombre' => 'Comidas', 'negocio_id' => $negocio->id]);

        return Item::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Tacos',
            'precio' => 80,
            'disponible_hoy' => true,
            'negocio_id' => $negocio->id,
        ]);
    }

    private function url(Negocio $negocio, Item $producto): string
    {
        return route('productos.disponibilidad', ['subdominio' => $negocio->subdominio, 'producto' => $producto]);
    }

    public function test_el_dueno_lo_marca_agotado_y_disponible_de_nuevo(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $this->seed(PermisosSeeder::class);
        $dueno->otorgarPermisosDeDueno();
        $producto = $this->producto($negocio);

        $this->actingAs($dueno)->patchJson($this->url($negocio, $producto))
            ->assertOk()->assertExactJson(['disponible' => false]);
        $this->assertFalse((bool) $producto->fresh()->disponible_hoy);

        $this->actingAs($dueno)->patchJson($this->url($negocio, $producto))
            ->assertOk()->assertExactJson(['disponible' => true]);
        $this->assertTrue((bool) $producto->fresh()->disponible_hoy);
    }

    public function test_un_empleado_con_marcar_agotado_puede_cambiarlo(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $this->seed(PermisosSeeder::class);
        $empleado->otorgarPermiso(ClavePermiso::MarcarAgotado, $negocio);
        $producto = $this->producto($negocio);

        $this->actingAs($empleado)->patchJson($this->url($negocio, $producto))->assertOk();

        $this->assertFalse((bool) $producto->fresh()->disponible_hoy);
    }

    public function test_empleado_sin_permiso_recibe_403(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $producto = $this->producto($negocio);

        $this->actingAs($empleado)->patchJson($this->url($negocio, $producto))->assertForbidden();

        $this->assertTrue((bool) $producto->fresh()->disponible_hoy);
    }

    public function test_un_dueno_no_cambia_productos_de_otro_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $dueno = Usuario::factory()->deNegocio($negocio)->create();
        $this->seed(PermisosSeeder::class);
        $dueno->otorgarPermisosDeDueno();
        $otro = Negocio::factory()->create();
        $ajeno = $this->producto($otro);

        $this->actingAs($dueno)->patchJson($this->url($otro, $ajeno))->assertNotFound();
        $this->actingAs($dueno)->patchJson($this->url($negocio, $ajeno))->assertNotFound();

        $this->assertTrue((bool) $ajeno->fresh()->disponible_hoy);
    }

    public function test_el_boton_aparece_en_la_categoria_para_el_empleado_con_permiso(): void
    {
        $negocio = Negocio::factory()->create();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $this->seed(PermisosSeeder::class);
        $empleado->otorgarPermiso(ClavePermiso::MarcarAgotado, $negocio);
        $producto = $this->producto($negocio);

        $url = route('panel.negocio.categoria', ['subdominio' => $negocio->subdominio, 'categoria' => $producto->categoria_id]);

        $this->actingAs($empleado)->get($url)
            ->assertOk()
            ->assertSee('x-data="disponibilidad(', false)
            ->assertDontSee('Agregar producto');
    }
}
