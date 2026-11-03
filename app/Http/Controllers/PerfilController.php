<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use App\Rules\Whatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * US-05: pantalla donde el dueño edita los datos de su negocio.
 * El negocio SIEMPRE sale de la sesion: no hay id en la URL ni en la
 * peticion, asi que un dueño no puede tocar el perfil de otro negocio.
 */
class PerfilController extends Controller
{
    public function edit(): View
    {
        return view('perfil.edit', ['negocio' => $this->negocioDeSesion()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $negocio = $this->negocioDeSesion();


        $request->merge([
            'telefono_whatsapp' => Whatsapp::normalizar($request->input('telefono_whatsapp')),
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono_whatsapp' => ['required', new Whatsapp],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nombre.required' => 'Escribe el nombre de tu negocio.',
            'nombre.max' => 'El nombre es demasiado largo.',
            'descripcion.max' => 'La descripción es demasiado larga.',
            'direccion.required' => 'Escribe la dirección de tu negocio.',
            'direccion.max' => 'La dirección es demasiado larga.',
            'telefono_whatsapp.required' => 'Escribe tu número de WhatsApp.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser una imagen JPG, PNG o WebP.',
            'logo.max' => 'El logo no puede pasar de 2 MB.',
        ]);

        if ($request->hasFile('logo')) {
            if ($negocio->logo_path) {
                Storage::disk('public')->delete($negocio->logo_path);
            }
            $datos['logo_path'] = $request->file('logo')->store('logos', 'public');
        }


        $negocio->update($datos);

        return redirect()->route('perfil.edit')->with('estado', 'Datos del negocio guardados.');
    }


    
    private function negocioDeSesion(): Negocio
    {
        $negocio = auth()->user()?->negocio;

        abort_unless($negocio instanceof Negocio, 403);

        return $negocio;
    }
}