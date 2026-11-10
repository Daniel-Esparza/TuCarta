<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoDisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_empleado_sin_permiso_recibe_403(): void
    {
        $empleado = Usuario::factory()->deNegocio()->create();

        $categoria = Categoria::create([
            'nombre' => 'Comidas',
            'negocio_id' => $empleado->negocio_id,
        ]);

        $producto = Item::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Tacos',
            'precio' => 80,
            'activo' => true,
            'disponible_hoy' => true,
            'negocio_id' => $empleado->negocio_id,
        ]);

        $url = route('productos.disponibilidad', [
            'subdominio' => $empleado->negocio->subdominio,
            'producto' => $producto,
        ]);

        $this->actingAs($empleado)
            ->patchJson($url)
            ->assertForbidden();

        $this->assertTrue($producto->fresh()->disponible_hoy);
    }
}
