<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class ModeradorController extends Controller
{
    public function index(): View
    {
        Gate::authorize('create', Usuario::class);

        return view('moderadores.index', [
            'moderadores' => Usuario::where('rol', Rol::Administrador->value)
                ->where('id', '!=', auth()->id())
                ->with(['permisos.permiso', 'permisos.negocio'])
                ->orderBy('nombre')
                ->get(),
            'permisos' => Permiso::where('tipo_usuario', Rol::Administrador->value)->orderBy('clave')->get(),
            'negocios' => Negocio::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Usuario::class);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'telefono_whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Moderador = administrador sin permisos asignados.
        Usuario::create($datos + ['rol' => Rol::Administrador, 'estado' => 'activo']);

        return back()->with('estado', 'Moderador creado.');
    }

    public function asignarPermiso(Request $request, Usuario $moderador): RedirectResponse
    {
        Gate::authorize('update', $moderador);

        $datos = $request->validate([
            'permiso_id' => ['required', 'exists:permisos,id'],
            'negocio_id' => ['nullable', 'exists:negocios,id'], // vacío = global
        ]);

        $permiso = Permiso::findOrFail($datos['permiso_id']);
        $negocio = isset($datos['negocio_id']) ? Negocio::findOrFail($datos['negocio_id']) : null;

        // otorgarPermiso rechaza permisos que no son del catálogo de administrador.
        try {
            $moderador->otorgarPermiso($permiso->claveEnum(), $negocio, $request->user());
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'permiso_id' => 'Ese permiso no pertenece al catálogo de administrador.',
            ]);
        }

        return back()->with('estado', 'Permiso asignado.');
    }

    public function quitarPermiso(Usuario $moderador, UsuarioPermiso $asignacion): RedirectResponse
    {
        Gate::authorize('update', $moderador);
        abort_unless($asignacion->usuario_id === $moderador->id, 404);

        $asignacion->delete();

        return back()->with('estado', 'Permiso quitado.');
    }
}
