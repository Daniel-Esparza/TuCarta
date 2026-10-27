<?php

namespace App\Http\Controllers;

use App\Enums\EstadoNegocio;
use App\Models\Negocio;
use App\Rules\DireccionWeb;
use App\Rules\Whatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * US-26a: formulario publico de auto-registro. No requiere sesion.
 * El negocio se guarda en estado "pendiente" y todavia no se crea cuenta (decision D1).
 */
class AutoRegistroNegocioController extends Controller
{
    /** Envios permitidos desde el mismo lugar dentro de la ventana de tiempo. */
    private const MAX_ENVIOS = 5;

    /** Ventana del limite en segundos (10 minutos). */
    private const SEGUNDOS_VENTANA = 600;

    public function create(): View
    {
        return view('auto-registro.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $clave = 'auto-registro|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_ENVIOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

            return back()->withInput()->with(
                'limite',
                'Hiciste demasiados envíos seguidos. Intenta de nuevo en '
                    .$minutos.($minutos === 1 ? ' minuto.' : ' minutos.')
            );
        }

        RateLimiter::hit($clave, self::SEGUNDOS_VENTANA);

        // Se quitan espacios, guiones y parentesis del WhatsApp antes de validar.
        $request->merge(['whatsapp' => Whatsapp::normalizar($request->input('whatsapp'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion_web' => ['required', new DireccionWeb],
            'whatsapp' => ['required', new Whatsapp],
            'correo_contacto' => ['required', 'email', 'max:255'],
        ], [
            'nombre.required' => 'Escribe el nombre de tu negocio.',
            'nombre.max' => 'El nombre es demasiado largo.',
            'direccion_web.required' => 'Escribe la dirección web de tu negocio.',
            'whatsapp.required' => 'Escribe tu número de WhatsApp.',
            'correo_contacto.required' => 'Escribe un correo de contacto.',
            'correo_contacto.email' => 'Escribe un correo electrónico válido.',
            'correo_contacto.max' => 'El correo es demasiado largo.',
        ]);

        Negocio::create([
            'nombre' => $datos['nombre'],
            'subdominio' => $datos['direccion_web'],
            'telefono_whatsapp' => $datos['whatsapp'],
            'correo_contacto' => $datos['correo_contacto'],
            'estado' => EstadoNegocio::Pendiente,
        ]);

        return redirect()->route('auto-registro.create')->with('enviado', true);
    }
}
