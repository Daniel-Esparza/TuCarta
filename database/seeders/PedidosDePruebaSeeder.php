<?php

namespace Database\Seeders;

use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Item;
use App\Models\Negocio;
use App\Models\Opcion;
use App\Models\Pedido;
use App\Models\PedidoEstadoHistorial;
use App\Models\PedidoItem;
use App\Models\PedidoItemOpcion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pedidos de Tacos Juan en distintos estados y fechas, para ver la bandeja (US-09).
 */
class PedidosDePruebaSeeder extends Seeder
{
    private const CAMINO = [
        'recibido' => ['recibido'],
        'en_preparacion' => ['recibido', 'en_preparacion'],
        'listo' => ['recibido', 'en_preparacion', 'listo'],
        'entregado' => ['recibido', 'en_preparacion', 'listo', 'entregado'],
        'cancelado' => ['recibido', 'cancelado'],
    ];

    public function run(): void
    {
        $negocio = Negocio::where('subdominio', 'tacos-juan')->first();

        if ($negocio === null || Pedido::withoutGlobalScopes()->where('negocio_id', $negocio->id)->exists()) {
            return;
        }

        $pedidos = [
            ['K7M2QX', 0, '13:10', 'recibido', 'domicilio', 'Lucía Hernández', [['Taco al pastor', 3, ['Queso']], ['Agua de horchata', 1]],
                'Sin cebolla, por favor.', 'Av. Himno Nacional 230, Centro', 'Portón negro junto a la farmacia'],
            ['P3HV8N', 0, '12:40', 'en_preparacion', 'para_llevar', 'Carlos Ruiz', [['Gringa', 2], ['Taco de suadero', 2]]],
            ['W9TB4D', 1, '20:15', 'listo', 'en_sitio', 'Ana Pérez', [['Taco al pastor', 4, ['Piña extra']]]],
            ['Z2LR6F', 2, '21:00', 'entregado', 'domicilio', 'Jorge Díaz', [['Gringa', 1], ['Agua de horchata', 2]],
                null, 'Calle Arista 815, Tequis', null],
            ['H5QC7J', 3, '19:30', 'entregado', 'en_sitio', 'María López', [['Taco de suadero', 5]]],
            ['N8XE3S', 5, '22:10', 'cancelado', 'para_llevar', 'Pedro Gómez', [['Taco al pastor', 2]]],
            ['R4GY9K', 9, '18:45', 'entregado', 'en_sitio', 'Sofía Torres', [['Gringa', 2], ['Taco al pastor', 2, ['Queso', 'Piña extra']]]],
        ];

        $zona = $negocio->zona_horaria ?: 'America/Mexico_City';

        foreach ($pedidos as $datos) {
            [$folio, $diasAtras, $hora, $estado, $tipo, $cliente, $lineas] = $datos;
            $fecha = Carbon::now($zona)->subDays($diasAtras)->setTimeFromTimeString($hora)->utc();

            $pedido = new Pedido([
                'negocio_id' => $negocio->id,
                'folio' => $folio,
                'cliente_nombre' => $cliente,
                'notas' => $datos[7] ?? null,
                'tipo_entrega' => TipoEntrega::from($tipo),
                'direccion_entrega' => $datos[8] ?? null,
                'referencias_entrega' => $datos[9] ?? null,
                'total' => '0.00',
                'estado' => EstadoPedido::from($estado),
            ]);
            $pedido->timestamps = false;
            $pedido->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

            $total = 0;
            foreach ($lineas as $linea) {
                $total += $this->agregarLinea($pedido, ...$linea);
            }
            $pedido->forceFill(['total' => number_format($total / 100, 2, '.', '')])->save();

            foreach (self::CAMINO[$estado] as $i => $paso) {
                $historial = new PedidoEstadoHistorial([
                    'pedido_id' => $pedido->id,
                    'negocio_id' => $negocio->id,
                    'estado_anterior' => $i === 0 ? null : EstadoPedido::from(self::CAMINO[$estado][$i - 1]),
                    'estado_nuevo' => EstadoPedido::from($paso),
                ]);
                $historial->forceFill(['created_at' => $fecha->copy()->addMinutes($i * 15)])->save();
            }
        }
    }

    // Devuelve el subtotal en centavos.
    private function agregarLinea(Pedido $pedido, string $producto, int $cantidad, array $opciones = []): int
    {
        $item = Item::withoutGlobalScopes()->where('negocio_id', $pedido->negocio_id)->where('nombre', $producto)->firstOrFail();
        $elegidas = Opcion::withoutGlobalScopes()->with('grupo')
            ->where('negocio_id', $pedido->negocio_id)->whereIn('nombre', $opciones)->get();

        $unitario = (int) round($item->precio * 100) + $elegidas->sum(fn ($opcion) => (int) round($opcion->incremento_precio * 100));

        $linea = PedidoItem::create([
            'pedido_id' => $pedido->id,
            'negocio_id' => $pedido->negocio_id,
            'item_id' => $item->id,
            'nombre_item' => $item->nombre,
            'precio_unitario' => number_format($unitario / 100, 2, '.', ''),
            'cantidad' => $cantidad,
            'subtotal' => number_format($unitario * $cantidad / 100, 2, '.', ''),
        ]);

        foreach ($elegidas as $opcion) {
            PedidoItemOpcion::create([
                'pedido_item_id' => $linea->id,
                'negocio_id' => $pedido->negocio_id,
                'opcion_id' => $opcion->id,
                'nombre_grupo' => $opcion->grupo->nombre,
                'nombre_opcion' => $opcion->nombre,
                'incremento_precio' => $opcion->incremento_precio,
            ]);
        }

        return $unitario * $cantidad;
    }
}
