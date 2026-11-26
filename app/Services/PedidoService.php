<?php

namespace App\Services;

use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Item;
use App\Models\Pedido;
use App\Models\PedidoEstadoHistorial;
use App\Models\PedidoItem;
use App\Models\PedidoItemOpcion;
use App\Support\NegocioActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * US-14a: crea el pedido a partir del carrito.
 * El navegador manda solo producto, cantidad y opciones. Los precios salen de la base.
 * Todo se guarda en una transacción: si algo falla, no queda nada a medias.
 */
class PedidoService
{
    private const ALFABETO_FOLIO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * @param  array<int, array{item_id:int, cantidad:int, opciones?:array<int,int>}>  $lineas
     */
    public function crear(
        string $clienteNombre,
        ?string $notas,
        array $lineas,
        ?string $ip = null,
        TipoEntrega $tipo = TipoEntrega::EnSitio,
        ?string $direccion = null,
        ?string $referencias = null,
    ): Pedido {
        if ($lineas === []) {
            throw ValidationException::withMessages(['carrito' => 'El carrito está vacío.']);
        }

        $items = Item::with('gruposOpciones.opciones')
            ->whereIn('id', collect($lineas)->pluck('item_id')->unique())
            ->get()
            ->keyBy('id');

        // Se valida todo antes de tocar la base.
        foreach ($lineas as $linea) {
            $item = $items->get($linea['item_id']);

            if (! $item || ! $item->activo || ! $item->disponible_hoy) {
                throw ValidationException::withMessages(['carrito' => 'Uno de los productos ya no está disponible.']);
            }

            $cantidad = (int) $linea['cantidad'];
            if ($cantidad < 1 || $cantidad > 99) {
                throw ValidationException::withMessages(['carrito' => 'La cantidad debe estar entre 1 y 99.']);
            }

            $this->validarOpciones($item, collect($linea['opciones'] ?? [])->map(fn ($id) => (int) $id)->unique());
        }

        return DB::transaction(function () use ($clienteNombre, $notas, $lineas, $ip, $tipo, $direccion, $referencias, $items) {
            $esDomicilio = $tipo === TipoEntrega::Domicilio;

            $pedido = Pedido::create([
                'folio' => $this->folioNuevo(),
                'cliente_nombre' => $clienteNombre,
                'notas' => $notas,
                'tipo_entrega' => $tipo,
                'direccion_entrega' => $esDomicilio ? $direccion : null,
                'referencias_entrega' => $esDomicilio ? $referencias : null,
                'total' => '0.00',
                'estado' => EstadoPedido::Recibido,
                'ip_origen' => $ip,
            ]);

            $totalCentavos = 0;

            foreach ($lineas as $linea) {
                $item = $items->get($linea['item_id']);
                $cantidad = (int) $linea['cantidad'];
                $elegidas = collect($linea['opciones'] ?? [])->map(fn ($id) => (int) $id)->unique();

                $mapa = $this->mapaDeOpciones($item);
                $extraCentavos = $elegidas->sum(fn ($id) => $this->centavos($mapa[$id]['opcion']->incremento_precio));

                $precioUnitario = $this->centavos($item->precio) + $extraCentavos;
                $subtotal = $precioUnitario * $cantidad;
                $totalCentavos += $subtotal;

                $pedidoItem = PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'item_id' => $item->id,
                    'nombre_item' => $item->nombre,
                    'precio_unitario' => $this->desdeCentavos($precioUnitario),
                    'cantidad' => $cantidad,
                    'subtotal' => $this->desdeCentavos($subtotal),
                ]);

                foreach ($elegidas as $opcionId) {
                    PedidoItemOpcion::create([
                        'pedido_item_id' => $pedidoItem->id,
                        'opcion_id' => $opcionId,
                        'nombre_grupo' => $mapa[$opcionId]['grupo']->nombre,
                        'nombre_opcion' => $mapa[$opcionId]['opcion']->nombre,
                        'incremento_precio' => $this->desdeCentavos($this->centavos($mapa[$opcionId]['opcion']->incremento_precio)),
                    ]);
                }
            }

            $pedido->update(['total' => $this->desdeCentavos($totalCentavos)]);

            PedidoEstadoHistorial::create([
                'pedido_id' => $pedido->id,
                'usuario_id' => null,
                'estado_anterior' => null,
                'estado_nuevo' => EstadoPedido::Recibido,
                'motivo' => null,
            ]);

            return $pedido->refresh();
        });
    }

    private function validarOpciones(Item $item, $elegidas): void
    {
        $mapa = $this->mapaDeOpciones($item);

        foreach ($elegidas as $opcionId) {
            if (! isset($mapa[$opcionId])) {
                throw ValidationException::withMessages(['carrito' => 'Una opción no corresponde al producto.']);
            }
        }

        foreach ($item->gruposOpciones as $grupo) {
            $cuantas = $elegidas->filter(fn ($id) => $mapa[$id]['grupo']->id === $grupo->id)->count();
            $minimo = $grupo->es_obligatorio ? max(1, (int) ($grupo->min_selecciones ?? 1)) : 0;
            $maximo = $grupo->es_multiple ? (int) ($grupo->max_selecciones ?? PHP_INT_MAX) : 1;

            if ($cuantas < $minimo || $cuantas > $maximo) {
                throw ValidationException::withMessages(['carrito' => "Revisa las opciones de «{$grupo->nombre}»."]);
            }
        }
    }

    // Mapa opcion_id => ['grupo' => GrupoOpcion, 'opcion' => Opcion] para un producto.
    private function mapaDeOpciones(Item $item): array
    {
        return $item->gruposOpciones
            ->flatMap(fn ($grupo) => $grupo->opciones->map(fn ($opcion) => [
                'grupo' => $grupo,
                'opcion' => $opcion,
            ]))
            ->keyBy(fn ($par) => $par['opcion']->id)
            ->all();
    }

    private function folioNuevo(): string
    {
        do {
            $folio = '';
            for ($i = 0; $i < 6; $i++) {
                $folio .= self::ALFABETO_FOLIO[random_int(0, strlen(self::ALFABETO_FOLIO) - 1)];
            }
        } while (Pedido::where('folio', $folio)->exists());

        return $folio;
    }

    // Montos en centavos enteros para no sumar con flotantes.
    private function centavos(string|float $monto): int
    {
        return (int) round((float) $monto * 100);
    }

    private function desdeCentavos(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
