<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Support\NegocioActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * US-09: bandeja de pedidos del panel del negocio.
 * El dueño y los empleados con ver_pedidos ven solo los pedidos de su negocio.
 * Las fechas se filtran y se muestran en la zona horaria del negocio.
 */
class PedidosPanelController extends Controller
{
    public function index(Request $request, NegocioActual $negocioActual): View
    {
        $negocio = $negocioActual->negocio();
        $zona = $this->zona($negocioActual);

        $filtros = Validator::make($request->query(), [
            'estado' => ['nullable', Rule::enum(EstadoPedido::class)],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'folio' => ['nullable', 'string', 'max:20'],
        ], [
            'estado.enum' => 'Elige un estado de la lista.',
            'desde.date_format' => 'La fecha inicial no es válida.',
            'hasta.date_format' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ])->validate();

        $pedidos = Pedido::where('negocio_id', $negocio->id)
            ->with('items:id,pedido_id,nombre_item,cantidad')
            ->when($filtros['estado'] ?? null, fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta
                ->where('created_at', '>=', Carbon::parse($desde, $zona)->startOfDay()->utc()))
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta
                ->where('created_at', '<=', Carbon::parse($hasta, $zona)->endOfDay()->utc()))
            ->when($filtros['folio'] ?? null, fn ($consulta, $folio) => $consulta
                ->where('folio', mb_strtoupper(trim($folio))))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'zona' => $zona,
            'hayFiltros' => array_filter($filtros) !== [],
        ]);
    }

    public function show(Pedido $pedido, NegocioActual $negocioActual): View
    {
        abort_unless($pedido->negocio_id === $negocioActual->id(), 404);

        $pedido->load([
            'items.opciones',
            'historial' => fn ($consulta) => $consulta->orderBy('created_at')->orderBy('id'),
        ]);

        return view('pedidos.show', [
            'pedido' => $pedido,
            'zona' => $this->zona($negocioActual),
        ]);
    }

    private function zona(NegocioActual $negocioActual): string
    {
        return $negocioActual->negocio()->zona_horaria ?: 'America/Mexico_City';
    }
}
