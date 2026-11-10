<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ProductoDisponibilidadController extends Controller
{
    public function alternar(Request $request, Item $producto)
    {
        abort_unless($producto->negocio_id === $request->user()->negocio_id, 404);
        abort_unless($request->user()->tienePermiso('marcar_agotado') || $request->user()->tienePermiso('gestionar_equipo'), 403);

        $producto->update(['disponible_hoy' => ! $producto->disponible_hoy]);

        return response()->json(['disponible' => (bool) $producto->disponible_hoy]);
    }
}
