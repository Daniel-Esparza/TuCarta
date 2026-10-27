<?php

namespace App\Http\Controllers;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    /**
     * Lista el equipo del negocio del dueño autenticado.
     */
    public function index(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $equipo = Usuario::where('negocio_id', $negocioId)
            ->with('permisos.permiso')
            ->orderBy('nombre')
            ->get();

        return view('equipo.index', [
            'equipo' => $equipo,
            'duenoId' => $request->user()->id,
        ]);
    }

    /**
     * Alta de empleado: rol negocio, mismo negocio que el dueño,
     * permisos avanzar_pedidos y marcar_agotado.
     */
    public function store(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'telefono_whatsapp' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'required' => 'Escribe el :attribute.',
            'email' => 'El correo no es válido.',
            'unique' => 'Ese correo ya está registrado.',
            'min' => 'El :attribute debe tener al menos :min caracteres.',
        ], [
            'nombre' => 'nombre',
            'apellidos' => 'apellido',
            'email' => 'correo',
            'telefono_whatsapp' => 'teléfono de WhatsApp',
            'password' => 'contraseña temporal',
        ]);

        // El negocio sale de la sesión: es imposible dar de alta
        // un empleado para el negocio de otra persona.
        $empleado = Usuario::create([
            'nombre' => $datos['nombre'],
            'apellidos' => $datos['apellidos'],
            'email' => $datos['email'],
            'telefono_whatsapp' => $datos['telefono_whatsapp'],
            'password' => $datos['password'],
            'rol' => Rol::Negocio,
            'negocio_id' => $negocioId,
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);

        // US-23: el empleado solo avanza pedidos y marca agotados.
        foreach ([ClavePermiso::AvanzarPedidos, ClavePermiso::MarcarAgotado] as $clave) {
            $empleado->otorgarPermiso($clave, $request->user()->negocio, $request->user());
        }

        return redirect()->route('equipo.index')
            ->with('exito', 'Empleado creado: ya puede iniciar sesión.');
    }

    /**
     * Desactivar a un empleado que ya no trabaja en el negocio.
     */
    public function desactivar(Request $request, Usuario $empleado)
    {
        $negocioId = $this->negocioDelDueno($request);

        // Si no es de mi negocio, no existe para mí (404, no 403).
        abort_unless($empleado->negocio_id === $negocioId, 404, 'Empleado no encontrado.');
        abort_if($empleado->id === $request->user()->id, 403, 'No puedes desactivar tu propia cuenta.');

        $empleado->update(['estado' => 'inactivo']);

        return redirect()->route('equipo.index')->with('exito', 'Empleado desactivado.');
    }

    /**
     * El negocio siempre sale del usuario en sesión.
     */
    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;

        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return $negocioId;
    }
}
