<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    private function url(Negocio $negocio): string
    {
        return 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio').'/';
    }

    private function categoria(Negocio $negocio): Categoria
    {
        return Categoria::create(['negocio_id' => $negocio->id, 'nombre' => 'Comida', 'orden' => 0]);
    }

    private function producto(Categoria $categoria, bool $disponible, string $nombre): Item
    {
        return Item::create([
            'negocio_id' => $categoria->negocio_id,
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => 25,
            'disponible_hoy' => $disponible,
        ]);
    }


    private function node(string $javascript): array
    {

        $ruta = json_encode(public_path('js/carrito-calculo.js'), JSON_UNESCAPED_SLASHES);
        $javascript = str_replace('__CALCULO__', $ruta, $javascript);

        $proceso = new Process(['node', '-e', $javascript]);
        $proceso->run();

        $this->assertSame(0, $proceso->getExitCode(), 'node falló: '.$proceso->getErrorOutput());

        return json_decode($proceso->getOutput(), true);
    }

    public function test_el_menu_muestra_el_boton_de_agregar_en_disponibles_y_no_en_agotados(): void
    {
        $negocio = Negocio::factory()->create();
        $categoria = $this->categoria($negocio);
        $disponible = $this->producto($categoria, true, 'Taco disponible');
        $agotado = $this->producto($categoria, false, 'Taco agotado');

        $this->get($this->url($negocio))
            ->assertOk()
            ->assertSee('data-carrito-agregar="'.$disponible->id.'"', false)
            ->assertDontSee('data-carrito-agregar="'.$agotado->id.'"', false)
            ->assertSee('Agotado');
    }

    public function test_el_detalle_muestra_el_boton_de_agregar_en_disponibles_y_no_en_agotados(): void
    {
        $negocio = Negocio::factory()->create();
        $categoria = $this->categoria($negocio);
        $disponible = $this->producto($categoria, true, 'Taco disponible');
        $agotado = $this->producto($categoria, false, 'Taco agotado');

        $base = 'http://'.$negocio->subdominio.'.'.config('tucarta.dominio');

        $this->get($base.'/producto/'.$disponible->id)
            ->assertOk()
            ->assertSee('data-carrito-agregar="'.$disponible->id.'"', false);

        $this->get($base.'/producto/'.$agotado->id)
            ->assertOk()
            ->assertDontSee('data-carrito-agregar="'.$agotado->id.'"', false)
            ->assertSee('Agotado');
    }

    public function test_subtotales_y_total_salen_correctos_incluidas_opciones_con_precio_extra(): void
    {
        $r = $this->node(<<<'JS'
const C = require(__CALCULO__);
const items = [
    { id: 1, precio: 25, grupos: [] },
    { id: 2, precio: 40, grupos: [
        { opciones: [{ id: 101, incremento: 0 }, { id: 102, incremento: 12.5 }] },
        { opciones: [{ id: 111, incremento: 5 }, { id: 112, incremento: 8 }] },
    ]},
];
const lineas = [
    { producto_id: 1, cantidad: 2, opciones: [] },
    { producto_id: 2, cantidad: 1, opciones: [102] },
    { producto_id: 2, cantidad: 3, opciones: [111] },
];
console.log(JSON.stringify({
    subtotales: [
        C.subtotalLinea(lineas[0], items[0]),
        C.subtotalLinea(lineas[1], items[1]),
        C.subtotalLinea(lineas[2], items[1]),
    ],
    total: C.total(lineas, items),
}));
JS);

        $this->assertSame(50.0, (float) $r['subtotales'][0]);   // 25 x 2
        $this->assertSame(52.5, (float) $r['subtotales'][1]);   // 40 + 12.50 de la opción
        $this->assertSame(135.0, (float) $r['subtotales'][2]);  // (40 + 5) x 3
        $this->assertSame(237.5, (float) $r['total']);          // 50 + 52.50 + 135
    }

    public function test_el_mismo_producto_con_opciones_distintas_ocupa_lineas_distintas(): void
    {
        $r = $this->node(<<<'JS'
const C = require(__CALCULO__);
console.log(JSON.stringify({
    claves: [
        C.claveLinea({ producto_id: 2, opciones: [111, 102] }),
        C.claveLinea({ producto_id: 2, opciones: [102, 111] }),
        C.claveLinea({ producto_id: 2, opciones: [111] }),
    ],
}));
JS);

        $this->assertSame('2|102,111', $r['claves'][0]);
        $this->assertSame($r['claves'][0], $r['claves'][1]);          // el orden no cambia la línea
        $this->assertNotSame($r['claves'][0], $r['claves'][2]);       // opciones distintas: línea distinta
    }

    public function test_la_estructura_del_pedido_nunca_incluye_precios(): void
    {
        $r = $this->node(<<<'JS'
const C = require(__CALCULO__);
const estructura = C.estructuraPedido(12, [
    { producto_id: 48, cantidad: 2, opciones: [103, 107], nombre: 'Taco', precio_unitario: 25 },
    { producto_id: 51, cantidad: 1, opciones: [] },
]);
console.log(JSON.stringify(estructura));
JS);

        
        $this->assertSame(12, $r['negocio_id']);
        $this->assertCount(2, $r['lineas']);
        $this->assertSame(['producto_id', 'cantidad', 'opciones'], array_keys($r['lineas'][0]));
        $this->assertSame(48, $r['lineas'][0]['producto_id']);
        $this->assertSame(2, $r['lineas'][0]['cantidad']);
        $this->assertSame([103, 107], $r['lineas'][0]['opciones']);
        $this->assertStringNotContainsString('precio', json_encode($r));
    }
}
