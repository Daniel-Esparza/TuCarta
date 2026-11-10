<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ListadoProductosTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private Usuario $dueno;

    private Categoria $tacos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::factory()->create();
        $this->dueno = Usuario::factory()->deNegocio($this->negocio)->create();
        $this->seed(PermisosSeeder::class);
        $this->dueno->otorgarPermisosDeDueno();
        $this->tacos = $this->categoria($this->negocio, 'Tacos');

        URL::defaults(['subdominio' => $this->negocio->subdominio]);
    }

    private function categoria(Negocio $negocio, string $nombre): Categoria
    {
        return Categoria::create(['negocio_id' => $negocio->id, 'nombre' => $nombre]);
    }

    private function producto(Categoria $categoria, string $nombre, array $extra = []): Item
    {
        return Item::create($extra + [
            'negocio_id' => $categoria->negocio_id,
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 25,
            'disponible_hoy' => true,
        ]);
    }

    public function test_la_lista_solo_muestra_productos_del_propio_negocio(): void
    {
        $this->producto($this->tacos, 'Taco de pastor');
        $this->producto($this->categoria(Negocio::factory()->create(), 'Sushi'), 'Rollo california');

        $this->actingAs($this->dueno)->get(route('productos.index'))
            ->assertOk()
            ->assertSee('Taco de pastor')
            ->assertSee('Tacos')
            ->assertSee('25.00')
            ->assertDontSee('Rollo california');
    }

    public function test_la_busqueda_ignora_mayusculas(): void
    {
        $this->producto($this->tacos, 'Taco de pastor');
        $this->producto($this->tacos, 'Agua de horchata');

        $this->actingAs($this->dueno)->get(route('productos.index', ['q' => 'PASTOR']))
            ->assertSee('Taco de pastor')
            ->assertDontSee('Agua de horchata');
    }

    public function test_filtra_por_categoria(): void
    {
        $bebidas = $this->categoria($this->negocio, 'Bebidas');
        $this->producto($this->tacos, 'Taco de pastor');
        $this->producto($bebidas, 'Agua de horchata');

        $this->actingAs($this->dueno)->get(route('productos.index', ['categoria' => $bebidas->id]))
            ->assertSee('Agua de horchata')
            ->assertDontSee('Taco de pastor');
    }

    public function test_filtra_por_disponibilidad(): void
    {
        $this->producto($this->tacos, 'Taco de pastor');
        $this->producto($this->tacos, 'Taco de lengua', ['disponible_hoy' => false]);

        $this->actingAs($this->dueno)->get(route('productos.index', ['disponibilidad' => 'agotado']))
            ->assertSee('Taco de lengua')
            ->assertDontSee('Taco de pastor');

        $this->actingAs($this->dueno)->get(route('productos.index', ['disponibilidad' => 'disponible']))
            ->assertSee('Taco de pastor')
            ->assertDontSee('Taco de lengua');
    }

    public function test_los_filtros_se_combinan_y_se_conservan_al_paginar(): void
    {
        foreach (range(1, 21) as $numero) {
            $this->producto($this->tacos, sprintf('Taco %02d', $numero));
        }

        $this->actingAs($this->dueno)->get(route('productos.index', ['q' => 'taco', 'categoria' => $this->tacos->id]))
            ->assertSee('Taco 20')
            ->assertDontSee('Taco 21')
            ->assertSee('q=taco', false);
    }

    public function test_cada_fila_trae_el_boton_de_disponibilidad(): void
    {
        $this->producto($this->tacos, 'Taco de pastor');

        $this->actingAs($this->dueno)->get(route('productos.index'))
            ->assertSee('x-data="disponibilidad(', false);
    }

    public function test_eliminar_quita_el_producto_y_un_pedido_anterior_se_conserva(): void
    {
        $producto = $this->producto($this->tacos, 'Taco de pastor', ['precio' => 30]);

        $pedidoId = DB::table('pedidos')->insertGetId([
            'negocio_id' => $this->negocio->id,
            'folio' => 'A-1',
            'cliente_nombre' => 'Lucía',
            'tipo_entrega' => 'para_llevar',
            'total' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $renglonId = DB::table('pedido_items')->insertGetId([
            'pedido_id' => $pedidoId,
            'negocio_id' => $this->negocio->id,
            'item_id' => $producto->id,
            'nombre_item' => 'Taco de pastor',
            'precio_unitario' => 30,
            'cantidad' => 2,
            'subtotal' => 60,
        ]);

        $this->actingAs($this->dueno)
            ->delete(route('productos.destroy', $producto))
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseMissing('items', ['id' => $producto->id]);
        $this->assertDatabaseHas('pedido_items', [
            'id' => $renglonId,
            'item_id' => null,
            'nombre_item' => 'Taco de pastor',
            'precio_unitario' => 30,
            'subtotal' => 60,
        ]);
        $this->assertDatabaseHas('pedidos', ['id' => $pedidoId, 'total' => 60]);
    }

    public function test_eliminar_borra_la_foto_del_producto(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('negocios/1/productos/a.webp', 'x');
        Storage::disk('public')->put('negocios/1/productos/a_miniatura.webp', 'x');

        $producto = $this->producto($this->tacos, 'Taco de pastor', [
            'imagen' => 'negocios/1/productos/a.webp',
            'miniatura' => 'negocios/1/productos/a_miniatura.webp',
        ]);

        $this->actingAs($this->dueno)->delete(route('productos.destroy', $producto))->assertRedirect();

        Storage::disk('public')->assertMissing(['negocios/1/productos/a.webp', 'negocios/1/productos/a_miniatura.webp']);
    }

    public function test_una_categoria_con_productos_sigue_sin_poder_eliminarse(): void
    {
        $this->producto($this->tacos, 'Taco de pastor');

        $this->actingAs($this->dueno)
            ->delete(route('categorias.destroy', $this->tacos))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categorias', ['id' => $this->tacos->id]);
    }

    public function test_un_dueno_no_elimina_un_producto_de_otro_negocio(): void
    {
        $ajeno = $this->producto($this->categoria(Negocio::factory()->create(), 'Sushi'), 'Rollo california');

        $this->actingAs($this->dueno)->delete(route('productos.destroy', $ajeno))->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $ajeno->id]);
    }

    public function test_un_empleado_sin_permiso_recibe_403(): void
    {
        $producto = $this->producto($this->tacos, 'Taco de pastor');
        $empleado = Usuario::factory()->deNegocio($this->negocio)->create();
        $empleado->otorgarPermiso(ClavePermiso::MarcarAgotado, $this->negocio);

        $this->actingAs($empleado)->get(route('productos.index'))->assertForbidden();
        $this->actingAs($empleado)->delete(route('productos.destroy', $producto))->assertForbidden();

        $this->assertDatabaseHas('items', ['id' => $producto->id]);
    }
}
