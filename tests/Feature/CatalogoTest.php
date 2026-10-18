<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Negocio;
use Database\Seeders\NegociosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_llena_el_catalogo_correctamente(): void
    {
        $this->seed(NegociosSeeder::class);

        $this->assertDatabaseCount('negocios', 2);
        $this->assertDatabaseCount('categorias', 5);
        $this->assertDatabaseCount('items', 7);
        $this->assertDatabaseCount('grupos_opciones', 1);
        $this->assertDatabaseCount('opciones', 3);
    }

    public function test_cada_item_pertenece_a_su_negocio(): void
    {
        $this->seed(NegociosSeeder::class);

        $pizza  = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();
        $dragon = Negocio::where('subdominio', 'dragon-z')->firstOrFail();

        $this->assertSame(3, Item::where('negocio_id', $pizza->id)->count());
        $this->assertSame(4, Item::where('negocio_id', $dragon->id)->count());

        // El item agotado quedó marcado
        $this->assertDatabaseHas('items', [
            'nombre' => 'Arroz frito con camaron',
            'disponible_hoy' => false,
        ]);
    }

    public function test_un_negocio_tiene_sus_horarios_y_relaciones(): void
    {
        $this->seed(NegociosSeeder::class);

        $pizza = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();

        $this->assertSame(7, $pizza->horarios()->count());   
        $this->assertSame(2, $pizza->categorias()->count());
    }
}