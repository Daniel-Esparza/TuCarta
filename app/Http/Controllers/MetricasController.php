<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Support\NegocioActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * US-10: métricas del negocio.
 * Todo se calcula en la base con consultas agrupadas y solo con pedidos
 * del negocio propio: el negocio_id va explícito y, además, el RLS del
 * Sprint 5 filtra la conexión. Los totales se leen del pedido guardado,
 * nunca del navegador.
 */
class MetricasController extends Controller
{
    // Ticket promedio: todos los pedidos menos los cancelados
    // (acuerdo del equipo Sprint 11; si cambia la lista, se cambia aquí).
    private const EXCLUIDOS_TICKET = [EstadoPedido::Cancelado];

    public function index(Request $request, NegocioActual $negocioActual): View
    {
        $datos = $this->validar($request);

        $hasta = isset($datos['hasta'])
            ? Carbon::parse($datos['hasta'])->endOfDay()
            : now()->endOfDay();
        $desde = isset($datos['desde'])
            ? Carbon::parse($datos['desde'])->startOfDay()
            : $hasta->copy()->startOfMonth()->startOfDay();

        $negocioId = $negocioActual->id();
        $rango = [$desde, $hasta];

        $totales = DB::table('pedidos')
            ->where('negocio_id', $negocioId)
            ->whereBetween('created_at', $rango)
            ->selectRaw('COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS ventas')
            ->first();

        $porEstado = DB::table('pedidos')
            ->where('negocio_id', $negocioId)
            ->whereBetween('created_at', $rango)
            ->groupBy('estado')
            ->selectRaw('estado, COUNT(*) AS cantidad')
            ->pluck('cantidad', 'estado')
            ->map(fn ($cantidad) => (int) $cantidad);

        // Ticket promedio: pedidos válidos (no cancelados) del periodo.
        $ticket = DB::table('pedidos')
            ->where('negocio_id', $negocioId)
            ->whereBetween('created_at', $rango)
            ->whereNotIn('estado', array_column(self::EXCLUIDOS_TICKET, 'value'))
            ->selectRaw('COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS ventas')
            ->first();

        // Top 10 por cantidad vendida. Se agrupa por item_id + nombre del
        // snapshot, así los productos eliminados o renombrados no se pierden.
        // El rango de fechas es el del PEDIDO (join), no el de la línea.
        $topProductos = DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->where('pedido_items.negocio_id', $negocioId)
            ->whereBetween('pedidos.created_at', $rango)
            ->groupBy('pedido_items.item_id', 'pedido_items.nombre_item')
            ->orderByDesc('cantidad_total')
            ->orderBy('pedido_items.nombre_item')
            ->limit(10)
            ->selectRaw('pedido_items.item_id, pedido_items.nombre_item, SUM(pedido_items.cantidad) AS cantidad_total')
            ->get();

        $pedidosTicket = (int) $ticket->pedidos;

        return view('panel.metricas', [
            'negocio'        => $negocioActual->negocio(),
            'desde'          => $desde->toDateString(),
            'hasta'          => $hasta->toDateString(),
            'hayPedidos'     => (int) $totales->pedidos > 0,
            'totalPedidos'   => (int) $totales->pedidos,
            'ventas'         => (float) $ticket->ventas,
            'ticketPromedio' => $pedidosTicket > 0 ? round((float) $ticket->ventas / $pedidosTicket, 2) : null,
            'porEstado'      => $porEstado,
            'topProductos'   => $topProductos,
        ]);
    }

    /**
     * Filtro por rango de fechas, ambas opcionales. Si falta una se
     * completa: sin fechas, el mes en curso; solo "hasta", ese mes.
     */
    private function validar(Request $request): array
    {
        $validador = Validator::make($request->query(), [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $validador->after(function ($validador) use ($request) {
            $desde = $request->query('desde');
            $hasta = $request->query('hasta');

            if ($desde && $hasta && strtotime($desde) > strtotime($hasta)) {
                $validador->errors()->add('desde', 'La fecha inicial no puede ser posterior a la final.');
            }
        });

        return $validador->validate();
    }
}
