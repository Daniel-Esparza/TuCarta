<?php

namespace Tests\Feature;

use App\Models\GrupoOpcion;
use App\Models\Horario;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Opcion;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoConfirmacionTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $pizzeria;
    private Item $margarita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->pizzeria = Negocio::where('subdominio', 'pizza-nova')->firstOrFail();
        $this->margarita = Item::withoutGlobalScopes()->where('nombre', 'Pizza Margarita')->firstOrFail();

        // Abierto todos los días, para que la prueba no dependa de la hora.
        Horario::withoutGlobalScopes()->where('negocio_id', $this->pizzeria->id)->delete();
        foreach (range(1, 7) as $dia) {
            Horario::create([
                'negocio_id' => $this->pizzeria->id,
                'dia_semana' => $dia,
                'hora_apertura' => '00:00:00',
                'hora_cierre' => '23:59:59',
            ]);
        }
    }

    private function url(string $ruta = '/pedido'): string
    {
        return 'http://pizza-nova.'.config('tucarta.dominio').$ruta;
    }

    // Primera opción de cada grupo obligatorio, para que el pedido sea válido.
    private function opcionesObligatorias(Item $item): array
    {
        return GrupoOpcion::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->where('es_obligatorio', true)
            ->get()
            ->map(fn ($grupo) => Opcion::withoutGlobalScopes()
                ->where('grupo_opcion_id', $grupo->id)
                ->orderBy('orden')
                ->first())
            ->filter()
            ->map(fn ($opcion) => $opcion->id)
            ->values()
            ->all();
    }

    private function linea(Item $item, int $cantidad = 1, array $extra = []): array
    {
        return array_merge([
            'item_id' => $item->id,
            'cantidad' => $cantidad,
            'opciones' => $this->opcionesObligatorias($item),
        ], $extra);
    }

    private function enviar(array $lineas, array $extra = [])
    {
        return $this->post($this->url(), array_merge([
            'cliente_nombre' => 'Ana',
            'carrito' => json_encode($lineas),
        ], $extra));
    }

    private function totalEsperado(Item $item, int $cantidad, array $opcionesIds): string
    {
        $extras = (float) Opcion::withoutGlobalScopes()->whereIn('id', $opcionesIds)->sum('incremento_precio');

        return number_format(((float) $item->precio + $extras) * $cantidad, 2, '.', '');
    }

    public function test_la_pantalla_de_confirmacion_abre(): void
    {
        $this->get($this->url())
            ->assertOk()
            ->assertSee('Confirmar pedido');
    }

    public function test_confirmar_guarda_el_pedido_sus_detalles_y_su_folio(): void
    {
        $opciones = $this->opcionesObligatorias($this->margarita);

        $this->enviar([$this->linea($this->margarita, 2)], ['notas' => 'Sin cebolla'])
            ->assertRedirect()
            ->assertSessionHas('folio');

        $pedido = Pedido::withoutGlobalScopes()->where('negocio_id', $this->pizzeria->id)->firstOrFail();
        $detalle = PedidoItem::withoutGlobalScopes()->where('pedido_id', $pedido->id)->get();

        $this->assertSame('Ana', $pedido->cliente_nombre);
        $this->assertSame('Sin cebolla', $pedido->notas);
        $this->assertSame('recibido', $pedido->estado->value);
        $this->assertSame(6, strlen($pedido->folio));
        $this->assertCount(1, $detalle);
        $this->assertSame(2, $detalle->first()->cantidad);
        $this->assertSame($this->totalEsperado($this->margarita, 2, $opciones), (string) $pedido->total);
    }

    public function test_sin_nombre_da_error_y_no_guarda(): void
    {
        $this->enviar([$this->linea($this->margarita)], ['cliente_nombre' => ''])
            ->assertSessionHasErrors('cliente_nombre');

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_la_nota_es_opcional(): void
    {
        $this->enviar([$this->linea($this->margarita)])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Pedido::withoutGlobalScopes()->count());
    }

    public function test_dos_pedidos_nunca_comparten_folio(): void
    {
        $this->enviar([$this->linea($this->margarita)]);
        $this->enviar([$this->linea($this->margarita)]);

        $folios = Pedido::withoutGlobalScopes()->pluck('folio');

        $this->assertCount(2, $folios);
        $this->assertCount(2, $folios->unique());
    }

    public function test_el_pedido_guarda_el_precio_del_dia(): void
    {
        $this->enviar([$this->linea($this->margarita)]);

        $pedido = Pedido::withoutGlobalScopes()->firstOrFail();
        $precioAntes = PedidoItem::withoutGlobalScopes()->where('pedido_id', $pedido->id)->value('precio_unitario');

        Item::withoutGlobalScopes()->whereKey($this->margarita->id)->update(['precio' => 999.00]);

        $this->assertSame($precioAntes, PedidoItem::withoutGlobalScopes()->where('pedido_id', $pedido->id)->value('precio_unitario'));
        $this->assertSame((string) $pedido->total, (string) Pedido::withoutGlobalScopes()->whereKey($pedido->id)->value('total'));
    }

    public function test_un_precio_alterado_en_la_peticion_se_ignora(): void
    {
        $this->enviar([$this->linea($this->margarita, 1, ['precio' => 0.01, 'total' => 0.01])]);

        $pedido = Pedido::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(
            $this->totalEsperado($this->margarita, 1, $this->opcionesObligatorias($this->margarita)),
            (string) $pedido->total
        );
    }

    public function test_un_producto_agotado_no_se_puede_pedir(): void
    {
        $this->margarita->update(['disponible_hoy' => false]);

        $this->enviar([$this->linea($this->margarita)])->assertSessionHasErrors();

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_un_producto_de_otro_negocio_se_rechaza(): void
    {
        $otro = Item::withoutGlobalScopes()
            ->where('negocio_id', '!=', $this->pizzeria->id)
            ->firstOrFail();

        $this->enviar([['item_id' => $otro->id, 'cantidad' => 1, 'opciones' => []]])
            ->assertSessionHasErrors();

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_si_una_linea_falla_no_queda_nada_guardado(): void
    {
        $this->enviar([
            $this->linea($this->margarita),
            $this->linea($this->margarita, 0),
        ])->assertSessionHasErrors();

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
        $this->assertSame(0, PedidoItem::withoutGlobalScopes()->count());
    }

    public function test_con_el_negocio_cerrado_el_servidor_rechaza_el_pedido(): void
    {
        $this->pizzeria->update(['cerrado_temporal' => true]);

        $this->enviar([$this->linea($this->margarita)])->assertForbidden();

        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_el_pedido_queda_ligado_al_negocio_de_la_direccion(): void
    {
        $this->enviar([$this->linea($this->margarita)]);

        $this->assertSame(
            $this->pizzeria->id,
            Pedido::withoutGlobalScopes()->firstOrFail()->negocio_id
        );
    }
}
