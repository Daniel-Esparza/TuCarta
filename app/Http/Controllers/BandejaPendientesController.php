<?php

namespace App\Http\Controllers;

use App\Enums\EstadoNegocio;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * US-26b: bandeja de los negocios que se auto-registraron (US-26a).
 * El administrador los aprueba o los rechaza con motivo.
 */
class BandejaPendientesController extends Controller
{
    public function index(): View
    {
        Gate::authorize('revisarPendientes', Negocio::class);

        return view('negocios.pendientes', [
            'negocios' => Negocio::where('estado', EstadoNegocio::Pendiente)
                ->oldest()
                ->paginate(20),
        ]);
    }

    public function aprobar(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('revisarPendientes', Negocio::class);
        abort_unless($negocio->estado === EstadoNegocio::Pendiente, 404);

        $correo = mb_strtolower($negocio->correo_contacto);

        if (Usuario::where('email', $correo)->exists()) {
            return redirect()->route('negocios.pendientes')->with(
                'error',
                "No se aprobó {$negocio->nombre}: ya existe una cuenta con el correo {$correo}."
            );
        }

        $contrasena = Str::password(12, symbols: false);

        // Negocio activo, cuenta del dueño y sus permisos se guardan juntos (decisión D1).
        DB::transaction(function () use ($negocio, $correo, $contrasena, $request) {
            $negocio->update(['estado' => EstadoNegocio::Activo]);

            $negocio->usuarios()->create([
                'rol' => Rol::Negocio,
                'nombre' => $negocio->nombre,
                'apellidos' => '',
                'email' => $correo,
                'correo_contacto' => $correo,
                'password' => $contrasena,
                'estado' => 'activo',
                'telefono_whatsapp' => $negocio->telefono_whatsapp,
            ])->otorgarPermisosDeDueno($request->user());
        });

        return redirect()->route('negocios.pendientes')->with('aprobado', [
            'negocio' => $negocio->nombre,
            'direccion' => $negocio->subdominio.'.'.config('tucarta.dominio'),
            'correo' => $correo,
            'contrasena' => $contrasena,
        ]);
    }

    public function rechazar(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('revisarPendientes', Negocio::class);
        abort_unless($negocio->estado === EstadoNegocio::Pendiente, 404);

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'motivo.required' => "Escribe el motivo para rechazar {$negocio->nombre}.",
            'motivo.max' => 'El motivo no puede pasar de 500 caracteres.',
        ]);

        $negocio->update([
            'estado' => EstadoNegocio::Rechazado,
            'motivo_rechazo' => $datos['motivo'],
        ]);

        return redirect()->route('negocios.pendientes')
            ->with('exito', "{$negocio->nombre} quedó rechazado. Su dirección web queda libre.");
    }
}
