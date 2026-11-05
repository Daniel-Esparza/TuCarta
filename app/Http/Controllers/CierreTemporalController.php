<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * US-19b: interruptor de cierre temporal del negocio (panel del dueño).
 * No hace falta migracion: la columna negocios.cerrado_temporal ya existe.
 */
class CierreTemporalController extends Controller
{
    public function index(Request $request): View
    {
        $negocio = $this->negocioDelDueno($request);

        return view('cierre-temporal', [
            'negocio' => $negocio,
            'dentroDeHorario' => $negocio->dentroDeHorario(),
        ]);
    }

    public function cerrar(Request $request): RedirectResponse
    {
        $this->negocioDelDueno($request)->update(['cerrado_temporal' => true]);

        return redirect()->route('cierre-temporal.index')
            ->with('exito', 'Tu negocio quedó cerrado temporalmente.');
    }

    public function reabrir(Request $request): RedirectResponse
    {
        $this->negocioDelDueno($request)->update(['cerrado_temporal' => false]);

        return redirect()->route('cierre-temporal.index')
            ->with('exito', 'Tu negocio volvió a su horario normal.');
    }

    /** Solo se toca el negocio de quien tiene la sesion; nunca uno que llegue en la peticion. */
    private function negocioDelDueno(Request $request): Negocio
    {
        $negocioId = $request->user()?->negocio_id;

        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return Negocio::findOrFail($negocioId);
    }
}
