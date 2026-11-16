<?php

namespace Tests\Feature\MenuPublico;

use App\Models\Item;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoDetalleTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $pizzeria;
    private Negocio $china;
    private Item $margarita;
    private Item $arrozAgotado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->pizzeria     = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();
        $this->china        = Negocio::where('subdominio', 'dragon-z')->firstOrFail();
        $this->margarita    = Item::withoutGlobalScopes()->where('nombre', 'Pizza Margarita')->firstOrFail();
        $this->arrozAgotado = Item::withoutGlobalScopes()->where('nombre', 'Arroz frito con camaron')->firstOrFail();
    }

    private function urlDetalle(Negocio $negocio, Item $item): string
    {
        return 'http://' . $negocio->subdominio . '.' . config('tucarta.dominio') . '/producto/' . $item->id;
    }

    public function test_el_detalle_muestra_nombre_precio_y_descripcion(): void
    {
        $respuesta = $this->get($this->urlDetalle($this->pizzeria, $this->margarita));

        $respuesta->assertOk();
        $respuesta->assertSee($this->margarita->nombre);
        $respuesta->assertSee(number_format($this->margarita->precio, 2));
        $respuesta->assertSee($this->margarita->descripcion);
    }

    public function test_el_detalle_muestra_los_grupos_de_opciones(): void
    {
        $respuesta = $this->get($this->urlDetalle($this->pizzeria, $this->margarita));

        $respuesta->assertOk();
        $respuesta->assertSee('Tamaño');
        $respuesta->assertSee('Chica');
        $respuesta->assertSee('Mediana');
        $respuesta->assertSee('Grande');
    }

    public function test_un_producto_agotado_muestra_etiqueta_agotado(): void
    {
        $respuesta = $this->get($this->urlDetalle($this->china, $this->arrozAgotado));

        $respuesta->assertOk();
        $respuesta->assertSee('Agotado');
    }

    public function test_el_detalle_de_un_producto_de_otro_negocio_devuelve_404(): void
    {
        $respuesta = $this->get($this->urlDetalle($this->china, $this->margarita));

        $respuesta->assertNotFound();
    }
}
