<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoDisponibilidadController extends Controller
{
    public function alternar(Request $request, Item $producto): JsonResponse
    {
        abort_unless($producto->negocio_id === $request->user()->negocio_id, 404);

        $producto->update(['disponible_hoy' => ! $producto->disponible_hoy]);

        return response()->json(['disponible' => (bool) $producto->disponible_hoy]);
    }
}
