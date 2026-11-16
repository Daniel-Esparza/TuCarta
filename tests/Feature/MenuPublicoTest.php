<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Horario;
use App\Models\Item;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function url(Negocio $negocio): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/';
    }

    private function categoriaConProducto(Negocio $negocio, string $nombre, int $orden, string $producto = 'Producto de prueba'): array
    {
        $categoria = Categoria::create([
            'negocio_id' => $negocio->id,
            'nombre' => $nombre,
            'orden' => $orden,
        ]);

        $item = Item::create([
            'negocio_id' => $negocio->id,
            'categoria_id' => $categoria->id,
            'nombre' => $producto,
            'precio' => 25,
        ]);

        return [$categoria, $item];
    }

    public function test_el_menu_muestra_logo_nombre_descripcion_y_horario_del_negocio(): void
    {
        $negocio = Negocio::factory()->create([
            'nombre' => 'Tacos Juan',
            'descripcion' => 'Taquería familiar desde 1998',
            'logo_path' => 'negocios/1/logo.webp',
        ]);
        Horario::create([
            'negocio_id' => $negocio->id,
            'dia_semana' => 1,
            'hora_apertura' => '10:00:00',
            'hora_cierre' => '18:00:00',
        ]);
        $this->categoriaConProducto($negocio, 'Tacos', 0);

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('Logo de Tacos Juan')
            ->assertSee('Tacos Juan')
            ->assertSee('Taquería familiar desde 1998')
            ->assertSee('Lunes')
            ->assertSee('10:00 a 18:00');
    }

    public function test_las_categorias_salen_en_el_orden_del_dueno_y_cambian_con_este(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Negocio de orden']);
        [$bebidas] = $this->categoriaConProducto($negocio, 'Bebidas', 0);
        [$tacos] = $this->categoriaConProducto($negocio, 'Tacos', 1);

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSeeInOrder(['Bebidas', 'Tacos']);

        // El dueño reordena: Tacos pasa arriba (US-06).
        $tacos->update(['orden' => 0]);
        $bebidas->update(['orden' => 1]);

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSeeInOrder(['Tacos', 'Bebidas']);
    }

    public function test_una_categoria_sin_productos_no_aparece(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Negocio sin vacias', 'descripcion' => 'Comida mexicana']);
        $this->categoriaConProducto($negocio, 'Tacos', 0);
        Categoria::create(['negocio_id' => $negocio->id, 'nombre' => 'Postres', 'orden' => 1]);

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('Tacos')
            ->assertDontSee('Postres');
    }

    public function test_un_producto_sin_foto_se_muestra_sin_error(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Negocio sin fotos']);
        $this->categoriaConProducto($negocio, 'Tacos', 0, 'Taco sin foto');

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('Taco sin foto');
    }

    public function test_un_visitante_sin_sesion_ve_el_menu_sin_redireccion(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Negocio publico']);
        $this->categoriaConProducto($negocio, 'Tacos', 0, 'Taco al pastor');

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('Taco al pastor');
    }

    public function test_solo_aparecen_productos_del_negocio_de_esa_direccion(): void
    {
        $tacos = Negocio::factory()->create(['nombre' => 'Tacos Juan']);
        $sushi = Negocio::factory()->create(['nombre' => 'Sushi Ana']);
        $this->categoriaConProducto($tacos, 'Tacos', 0, 'Taco al pastor');
        $this->categoriaConProducto($sushi, 'Rollos', 0, 'Rollito philadelphia');

        $this->get($this->url($tacos))
            ->assertOk()
            ->assertSee('Taco al pastor')
            ->assertDontSee('Rollito philadelphia');

        $this->get($this->url($sushi))
            ->assertOk()
            ->assertSee('Rollito philadelphia')
            ->assertDontSee('Taco al pastor');
    }

    public function test_un_producto_inactivo_no_aparece_en_el_menu(): void
    {
        $negocio = Negocio::factory()->create(['nombre' => 'Negocio con ocultos']);
        [$categoria] = $this->categoriaConProducto($negocio, 'Tacos', 0, 'Taco visible');

        Item::create([
            'negocio_id' => $negocio->id,
            'categoria_id' => $categoria->id,
            'nombre' => 'Taco oculto',
            'precio' => 30,
            'activo' => false,
        ]);

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('Taco visible')
            ->assertDontSee('Taco oculto');
    }
}