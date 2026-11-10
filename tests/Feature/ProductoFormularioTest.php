<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProductoFormularioTest extends TestCase
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

    private function categoria(Negocio $negocio): Categoria
    {
        return Categoria::create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Tacos',
            'orden' => 0,
        ]);
    }

    private function datosValidos(Categoria $categoria): array
    {
        return [
            'nombre' => 'Taco al pastor',
            'descripcion' => 'Con piña y cilantro',
            'precio' => '18.50',
            'categoria_id' => $categoria->id,
            'disponible_hoy' => '1',
        ];
    }

    public function test_el_dueno_crea_un_producto_y_lo_ve_en_su_categoria(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        $this->actingAs($dueno)
            ->post(route('productos.store'), $this->datosValidos($categoria))
            ->assertRedirect(route('panel.negocio.categoria', $categoria->id))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('items', [
            'negocio_id' => $negocio->id,
            'categoria_id' => $categoria->id,
            'nombre' => 'Taco al pastor',
            'precio' => '18.50',
            'disponible_hoy' => true,
        ]);
    }

    public function test_un_producto_se_puede_guardar_como_agotado(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        
        $this->actingAs($dueno)
            ->post(route('productos.store'), [
                'nombre' => 'Flan casero',
                'precio' => 35,
                'categoria_id' => $categoria->id,
            ])
            ->assertRedirect(route('panel.negocio.categoria', $categoria->id));

        $this->assertDatabaseHas('items', [
            'nombre' => 'Flan casero',
            'disponible_hoy' => false,
        ]);
    }

    public function test_el_dueno_edita_un_producto(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $tacos = $this->categoria($negocio);
        $bebidas = Categoria::create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas', 'orden' => 1]);

        $producto = Item::create([
            'negocio_id' => $negocio->id,
            'categoria_id' => $tacos->id,
            'nombre' => 'Taco suizo',
            'precio' => 20,
        ]);

        $this->actingAs($dueno)
            ->put(route('productos.update', $producto), [
                'nombre' => 'Taco suizo gratinado',
                'descripcion' => 'Con queso',
                'precio' => '22',
                'categoria_id' => $bebidas->id,
            ])
            ->assertRedirect(route('panel.negocio.categoria', $bebidas->id))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('items', [
            'id' => $producto->id,
            'categoria_id' => $bebidas->id,
            'nombre' => 'Taco suizo gratinado',
            'precio' => 22,
            'disponible_hoy' => false,
        ]);
    }

    public function test_el_formulario_muestra_los_campos_y_las_categorias_del_negocio(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        $this->actingAs($dueno)
            ->get(route('productos.create'))
            ->assertOk()
            ->assertSeeInOrder(['Nombre', 'Descripción', 'Precio', 'Categoría', 'Imagen', 'Disponible hoy'])
            ->assertSee($categoria->nombre);
    }

    public function test_sin_nombre_sin_precio_o_sin_categoria_da_error_por_campo(): void
    {
        [, $dueno] = $this->duenoConPermiso();

        $this->actingAs($dueno)
            ->post(route('productos.store'), [])
            ->assertSessionHasErrors(['nombre', 'precio', 'categoria_id']);
    }

    public function test_el_precio_cero_negativo_o_con_letras_da_error(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        foreach ([0, -5, 'abc'] as $precio) {
            $this->actingAs($dueno)
                ->post(route('productos.store'), [
                    'nombre' => 'X',
                    'precio' => $precio,
                    'categoria_id' => $categoria->id,
                ])
                ->assertSessionHasErrors('precio');
        }

        $this->assertDatabaseCount('items', 0);
    }

    public function test_una_categoria_de_otro_negocio_se_rechaza(): void
    {
        [, $dueno] = $this->duenoConPermiso();
        $ajena = $this->categoria(Negocio::factory()->create());

        $this->actingAs($dueno)
            ->post(route('productos.store'), [
                'nombre' => 'Infiltrado',
                'precio' => 10,
                'categoria_id' => $ajena->id,
            ])
            ->assertSessionHasErrors('categoria_id');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_un_dueno_no_edita_productos_de_otro_negocio(): void
    {
        [, $dueno] = $this->duenoConPermiso();
        $otro = Negocio::factory()->create();
        $categoriaAjena = $this->categoria($otro);

        $producto = Item::create([
            'negocio_id' => $otro->id,
            'categoria_id' => $categoriaAjena->id,
            'nombre' => 'Producto ajeno',
            'precio' => 50,
        ]);

        $this->actingAs($dueno)->get(route('productos.edit', $producto))->assertNotFound();

        $this->actingAs($dueno)
            ->put(route('productos.update', $producto), [
                'nombre' => 'Hackeado',
                'precio' => 1,
                'categoria_id' => $categoriaAjena->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $producto->id, 'nombre' => 'Producto ajeno']);
    }

    public function test_un_empleado_sin_permiso_no_entra(): void
    {
        [$negocio] = $this->duenoConPermiso();
        $empleado = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($empleado)->get(route('productos.create'))->assertForbidden();
        $this->actingAs($empleado)->post(route('productos.store'), [])->assertForbidden();
    }

    public function test_un_empleado_con_permiso_ve_el_formulario(): void
    {
        [$negocio] = $this->duenoConPermiso();
        $this->categoria($negocio);
        $empleado = Usuario::factory()->deNegocio($negocio)->create();
        $empleado->otorgarPermiso(ClavePermiso::EditarPerfil, $negocio);

        $this->actingAs($empleado)
            ->get(route('productos.create'))
            ->assertOk()
            ->assertSee('Nuevo producto');
    }

    public function test_una_imagen_con_tipo_no_permitido_da_error(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        $this->actingAs($dueno)
            ->post(route('productos.store'), $this->datosValidos($categoria) + [
                'imagen' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_una_imagen_de_mas_de_5_mb_da_error(): void
    {
        [$negocio, $dueno] = $this->duenoConPermiso();
        $categoria = $this->categoria($negocio);

        $this->actingAs($dueno)
            ->post(route('productos.store'), $this->datosValidos($categoria) + [
                'imagen' => UploadedFile::fake()->create('foto.jpg', 5300, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_un_invitado_sin_sesion_va_al_login(): void
    {
        [$negocio] = $this->duenoConPermiso();

        auth()->logout();

        $this->get('http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/panel/productos/crear')
            ->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }
}