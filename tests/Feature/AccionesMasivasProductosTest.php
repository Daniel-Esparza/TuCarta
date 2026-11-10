<?php

namespace Tests\Feature;

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

class AccionesMasivasProductosTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private Usuario $dueno;

    private Categoria $tacos;

    private Categoria $bebidas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::factory()->create();
        $this->dueno = Usuario::factory()->deNegocio($this->negocio)->create();
        $this->seed(PermisosSeeder::class);
        $this->dueno->otorgarPermisosDeDueno();
        $this->tacos = $this->categoria($this->negocio, 'Tacos');
        $this->bebidas = $this->categoria($this->negocio, 'Bebidas');

        URL::defaults(['subdominio' => $this->negocio->subdominio]);
    }

    private function categoria(Negocio $negocio, string $nombre): Categoria
    {
        return Categoria::create(['negocio_id' => $negocio->id, 'nombre' => $nombre]);
    }

    private function producto(Categoria $categoria, string $nombre): Item
    {
        return Item::create([
            'negocio_id' => $categoria->negocio_id,
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 25,
        ]);
    }

    private function grupoConOpciones(Item $item, string $nombre = 'Extras'): GrupoOpcion
    {
        $grupo = GrupoOpcion::create([
            'item_id' => $item->id,
            'negocio_id' => $item->negocio_id,
            'nombre' => $nombre,
            'es_multiple' => true,
            'es_obligatorio' => false,
            'min_selecciones' => 0,
            'max_selecciones' => 3,
        ]);

        foreach (['Queso' => 15, 'Tocino' => 20] as $opcion => $precio) {
            Opcion::create([
                'grupo_opcion_id' => $grupo->id,
                'negocio_id' => $item->negocio_id,
                'nombre' => $opcion,
                'incremento_precio' => $precio,
            ]);
        }

        return $grupo;
    }

    public function test_mueve_los_productos_seleccionados(): void
    {
        $uno = $this->producto($this->tacos, 'Uno');
        $otro = $this->producto($this->tacos, 'Otro');

        $this->actingAs($this->dueno)->post(route('productos.mover'), [
            'categoria_id' => $this->bebidas->id,
            'productos' => [$uno->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('exito');

        $this->assertDatabaseHas('items', ['id' => $uno->id, 'categoria_id' => $this->bebidas->id]);
        $this->assertDatabaseHas('items', ['id' => $otro->id, 'categoria_id' => $this->tacos->id]);
    }

    public function test_mueve_todos_los_resultados_de_la_busqueda(): void
    {
        $uno = $this->producto($this->tacos, 'Hamburguesa clásica');
        $dos = $this->producto($this->tacos, 'Hamburguesa doble');
        $otro = $this->producto($this->tacos, 'Taco de pastor');

        $this->actingAs($this->dueno)->post(route('productos.mover'), [
            'categoria_id' => $this->bebidas->id,
            'todos' => 1,
            'q' => 'hamburguesa',
        ])->assertSessionHas('exito', 'Se movieron 2 producto(s).');

        $this->assertDatabaseHas('items', ['id' => $uno->id, 'categoria_id' => $this->bebidas->id]);
        $this->assertDatabaseHas('items', ['id' => $dos->id, 'categoria_id' => $this->bebidas->id]);
        $this->assertDatabaseHas('items', ['id' => $otro->id, 'categoria_id' => $this->tacos->id]);
    }

    public function test_mover_sin_seleccion_da_error(): void
    {
        $this->actingAs($this->dueno)->post(route('productos.mover'), [
            'categoria_id' => $this->bebidas->id,
        ])->assertSessionHasErrors('productos');
    }

    public function test_no_mueve_productos_ajenos_ni_a_categorias_ajenas(): void
    {
        $ajena = $this->categoria(Negocio::factory()->create(), 'Sushi');
        $ajeno = $this->producto($ajena, 'Rollo');
        $propio = $this->producto($this->tacos, 'Taco');

        $this->actingAs($this->dueno)->post(route('productos.mover'), [
            'categoria_id' => $ajena->id,
            'productos' => [$propio->id],
        ])->assertSessionHasErrors('categoria_id');

        $this->actingAs($this->dueno)->post(route('productos.mover'), [
            'categoria_id' => $this->bebidas->id,
            'productos' => [$ajeno->id],
        ])->assertSessionHasErrors('productos');

        $this->assertDatabaseHas('items', ['id' => $propio->id, 'categoria_id' => $this->tacos->id]);
        $this->assertDatabaseHas('items', ['id' => $ajeno->id, 'categoria_id' => $ajena->id]);
    }

    public function test_copia_los_grupos_a_toda_una_categoria_y_a_un_producto_suelto(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $enCategoria = $this->producto($this->tacos, 'Hermano');
        $suelto = $this->producto($this->bebidas, 'Suelto');
        $sinTocar = $this->producto($this->bebidas, 'Sin tocar');
        $grupo = $this->grupoConOpciones($origen);

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $origen), [
            'grupos' => [$grupo->id],
            'categorias' => [$this->tacos->id],
            'productos' => [$suelto->id],
        ])->assertRedirect(route('grupos.index', $origen))->assertSessionHasNoErrors();

        foreach ([$enCategoria, $suelto] as $destino) {
            $copia = GrupoOpcion::where('item_id', $destino->id)->with('opciones')->firstOrFail();

            $this->assertSame('Extras', $copia->nombre);
            $this->assertSame(['Queso', 'Tocino'], $copia->opciones->pluck('nombre')->sort()->values()->all());
        }

        $this->assertDatabaseMissing('grupos_opciones', ['item_id' => $sinTocar->id]);
        $this->assertSame(1, GrupoOpcion::where('item_id', $origen->id)->count());
    }

    public function test_un_grupo_con_el_mismo_nombre_se_respeta_salvo_que_se_pida_reemplazar(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $destino = $this->producto($this->bebidas, 'Destino');
        $grupo = $this->grupoConOpciones($origen);
        $existente = GrupoOpcion::create([
            'item_id' => $destino->id,
            'negocio_id' => $this->negocio->id,
            'nombre' => 'EXTRAS',
        ]);

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $origen), [
            'grupos' => [$grupo->id],
            'productos' => [$destino->id],
        ]);

        $this->assertDatabaseHas('grupos_opciones', ['id' => $existente->id]);
        $this->assertSame(1, GrupoOpcion::where('item_id', $destino->id)->count());

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $origen), [
            'grupos' => [$grupo->id],
            'productos' => [$destino->id],
            'reemplazar' => 1,
        ]);

        $this->assertDatabaseMissing('grupos_opciones', ['id' => $existente->id]);
        $this->assertDatabaseHas('grupos_opciones', ['item_id' => $destino->id, 'nombre' => 'Extras']);
    }

    public function test_copiar_sin_destino_da_error(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $grupo = $this->grupoConOpciones($origen);

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $origen), ['grupos' => [$grupo->id]])
            ->assertSessionHasErrors('destinos');
    }

    public function test_no_copia_hacia_ni_desde_otro_negocio(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $grupo = $this->grupoConOpciones($origen);
        $ajeno = $this->producto($this->categoria(Negocio::factory()->create(), 'Sushi'), 'Rollo');

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $origen), [
            'grupos' => [$grupo->id],
            'productos' => [$ajeno->id],
        ])->assertSessionHasErrors('destinos');

        $this->actingAs($this->dueno)->post(route('grupos.copiar', $ajeno), [
            'grupos' => [$grupo->id],
            'productos' => [$origen->id],
        ])->assertNotFound();

        $this->assertDatabaseMissing('grupos_opciones', ['item_id' => $ajeno->id]);
    }

    public function test_un_empleado_sin_permiso_recibe_403(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $empleado = Usuario::factory()->deNegocio($this->negocio)->create();

        $this->actingAs($empleado)->post(route('grupos.copiar', $origen), [])->assertForbidden();
        $this->actingAs($empleado)->post(route('productos.mover'), [])->assertForbidden();
    }

    public function test_un_producto_nuevo_puede_copiar_las_opciones_de_otro(): void
    {
        $origen = $this->producto($this->tacos, 'Origen');
        $this->grupoConOpciones($origen);

        $this->actingAs($this->dueno)->post(route('productos.store'), [
            'nombre' => 'Nuevo',
            'precio' => 30,
            'categoria_id' => $this->tacos->id,
            'copiar_de' => $origen->id,
        ])->assertRedirect(route('productos.index'));

        $nuevo = Item::where('nombre', 'Nuevo')->firstOrFail();

        $this->assertSame(2, Opcion::whereIn('grupo_opcion_id', GrupoOpcion::where('item_id', $nuevo->id)->pluck('id'))->count());
    }

    public function test_al_guardar_cambios_vuelve_a_la_lista_de_productos(): void
    {
        $producto = $this->producto($this->tacos, 'Taco');

        $this->actingAs($this->dueno)->put(route('productos.update', $producto), [
            'nombre' => 'Taco',
            'precio' => 30,
            'categoria_id' => $this->tacos->id,
        ])->assertRedirect(route('productos.index'));
    }
}
