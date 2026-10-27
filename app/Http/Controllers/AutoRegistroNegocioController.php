<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
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

    /**
     * TODO (base de Daniel): cuando llegue a dev la clase unica de la regla de direccion web
     * y la lista de palabras reservadas en configuracion, reemplazar esta lista y las reglas
     * de "direccion_web" por las de Daniel. No dejar una copia aparte.
     */
    private const RESERVADAS = ['www', 'admin', 'api', 'app', 'mail', 'auth', 'traefik'];

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
        $request->merge([
            'whatsapp' => preg_replace('/[\s\-().]/', '', (string) $request->input('whatsapp')),
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion_web' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-z0-9-]+$/',
                Rule::notIn(self::RESERVADAS),
                Rule::unique('negocios', 'subdominio'),
            ],
            'whatsapp' => ['required', 'regex:/^\+\d{11,13}$/'],
            'correo_contacto' => ['required', 'email', 'max:255'],
        ], [
            'nombre.required' => 'Escribe el nombre de tu negocio.',
            'nombre.max' => 'El nombre es demasiado largo.',
            'direccion_web.required' => 'Escribe la dirección web de tu negocio.',
            'direccion_web.min' => 'La dirección web debe tener al menos 3 caracteres.',
            'direccion_web.max' => 'La dirección web no puede pasar de 30 caracteres.',
            'direccion_web.regex' => 'Usa solo minúsculas, números y guion, sin espacios.',
            'direccion_web.not_in' => 'Esa dirección web no está disponible.',
            'direccion_web.unique' => 'Esa dirección web ya está en uso.',
            'whatsapp.required' => 'Escribe tu número de WhatsApp.',
            'whatsapp.regex' => 'Escribe el WhatsApp con lada y 10 dígitos, por ejemplo +524441234567.',
            'correo_contacto.required' => 'Escribe un correo de contacto.',
            'correo_contacto.email' => 'Escribe un correo electrónico válido.',
            'correo_contacto.max' => 'El correo es demasiado largo.',
        ]);

        // TODO (Daniel): "direccion" y "categoria" son obligatorias en la tabla y este formulario
        // no las pide. Se envia un valor temporal hasta que Daniel las haga opcionales.
        // TODO: usar el estado de la base compartida de Daniel en lugar del texto.
        Negocio::create([
            'nombre' => $datos['nombre'],
            'subdominio' => $datos['direccion_web'],
            'telefono_whatsapp' => $datos['whatsapp'],
            'correo_contacto' => $datos['correo_contacto'],
            'direccion' => 'Por definir',
            'categoria' => 'Por definir',
            'estado' => 'pendiente',
        ]);

        return redirect()->route('auto-registro.create')->with('enviado', true);
    }
}
