<?php

namespace App\Http\Controllers;

use App\Enums\EstadoNegocio;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Rules\DireccionWeb;
use App\Rules\Whatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * US-02a: el administrador da de alta un negocio junto con la cuenta de su dueño.
 */
class AltaNegocioController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Negocio::class);

        return view('negocios.crear');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Negocio::class);

        $request->merge([
            'whatsapp' => Whatsapp::normalizar($request->input('whatsapp')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion_web' => ['required', new DireccionWeb],
            'whatsapp' => ['required', new Whatsapp],
            'ubicacion' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'nombre_dueno' => ['required', 'string', 'max:255'],
            'apellidos_dueno' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'nombre.required' => 'Escribe el nombre del negocio.',
            'direccion_web.required' => 'Escribe la dirección web del negocio.',
            'whatsapp.required' => 'Escribe el WhatsApp del negocio.',
            'ubicacion.required' => 'Escribe la ubicación del negocio.',
            'nombre_dueno.required' => 'Escribe el nombre del dueño.',
            'apellidos_dueno.required' => 'Escribe los apellidos del dueño.',
            'email.required' => 'Escribe el correo del dueño.',
            'email.email' => 'Escribe un correo válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'Escribe una contraseña para el dueño.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // Negocio, dueño y permisos se guardan juntos: si algo falla no queda nada a medias.
        $negocio = DB::transaction(function () use ($datos, $request) {
            $negocio = Negocio::create([
                'nombre' => $datos['nombre'],
                'subdominio' => $datos['direccion_web'],
                'telefono_whatsapp' => $datos['whatsapp'],
                'correo_contacto' => $datos['email'],
                'direccion' => $datos['ubicacion'],
                'descripcion' => $datos['descripcion'] ?? null,
                'estado' => EstadoNegocio::Activo,
            ]);

            $dueno = $negocio->usuarios()->create([
                'rol' => Rol::Negocio,
                'nombre' => $datos['nombre_dueno'],
                'apellidos' => $datos['apellidos_dueno'],
                'email' => $datos['email'],
                'correo_contacto' => $datos['email'],
                'password' => $datos['password'],
                'estado' => 'activo',
                'telefono_whatsapp' => $datos['whatsapp'],
            ]);

            $dueno->otorgarPermisosDeDueno($request->user());

            return $negocio;
        });

        return redirect()->route('negocios.index')->with(
            'estado',
            "{$negocio->nombre} quedó activo en {$negocio->subdominio}.".config('tucarta.dominio').'. Su dueño ya puede iniciar sesión.'
        );
    }
}
