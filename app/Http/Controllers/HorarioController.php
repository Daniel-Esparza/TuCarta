<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HorarioController extends Controller
{
    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function index(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $horarios = Horario::where('negocio_id', $negocioId)
            ->orderBy('dia_semana')
            ->orderBy('hora_apertura')
            ->get()
            ->groupBy('dia_semana');

        return view('horarios.index', ['dias' => self::DIAS, 'horarios' => $horarios]);
    }

    public function store(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $datos = $request->validate([
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'different:hora_apertura'],
        ], [
            'required' => 'Escribe :attribute.',
            'between' => 'Elige un día válido.',
            'date_format' => 'Usa el formato de hora HH:MM.',
            'different' => 'La hora de cierre no puede ser igual a la de apertura.',
        ], [
            'dia_semana' => 'el día',
            'hora_apertura' => 'la hora de apertura',
            'hora_cierre' => 'la hora de cierre',
        ]);

        $tramos = $this->tramos(
            (int) $datos['dia_semana'],
            $datos['hora_apertura'].':00',
            $datos['hora_cierre'].':00',
        );

        foreach ($tramos as [$dia, $apertura, $cierre]) {
            if (Horario::seTraslapa($negocioId, $dia, $apertura, $cierre)) {
                throw ValidationException::withMessages([
                    'hora_apertura' => 'Este horario se traslapa con otro del mismo día.',
                ]);
            }
        }

        DB::transaction(function () use ($tramos, $negocioId) {
            foreach ($tramos as [$dia, $apertura, $cierre]) {
                Horario::create([
                    'negocio_id' => $negocioId,
                    'dia_semana' => $dia,
                    'hora_apertura' => $apertura,
                    'hora_cierre' => $cierre,
                ]);
            }
        });

        return redirect()->route('horarios.index')->with('exito', 'Horario guardado.');
    }

    public function destroy(Request $request, Horario $horario)
    {
        $negocioId = $this->negocioDelDueno($request);

        // Si no es de mi negocio, no existe para mí (404, no 403).
        abort_unless($horario->negocio_id === $negocioId, 404, 'Horario no encontrado.');

        DB::transaction(function () use ($horario, $negocioId) {
            // Un rango que cruza la medianoche son dos filas: se borran juntas.
            if ($horario->hora_cierre === Horario::FIN_DE_DIA) {
                Horario::where('negocio_id', $negocioId)
                    ->where('dia_semana', $horario->dia_semana === 7 ? 1 : $horario->dia_semana + 1)
                    ->where('hora_apertura', '00:00:00')
                    ->delete();
            }
            if ($horario->hora_apertura === '00:00:00') {
                Horario::where('negocio_id', $negocioId)
                    ->where('dia_semana', $horario->dia_semana === 1 ? 7 : $horario->dia_semana - 1)
                    ->where('hora_cierre', Horario::FIN_DE_DIA)
                    ->delete();
            }
            $horario->delete();
        });

        return redirect()->route('horarios.index')->with('exito', 'Horario eliminado.');
    }

    /**
     * Parte un rango en tramos de un solo día.
     * 17:00 a 01:00 del lunes: [lunes 17:00-23:59:59] y [martes 00:00-01:00].
     */
    private function tramos(int $dia, string $apertura, string $cierre): array
    {
        if ($cierre > $apertura) {
            return [[$dia, $apertura, $cierre]];
        }

        $tramos = [[$dia, $apertura, Horario::FIN_DE_DIA]];
        if ($cierre !== '00:00:00') {
            $tramos[] = [$dia === 7 ? 1 : $dia + 1, '00:00:00', $cierre];
        }

        return $tramos;
    }

    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;

        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return $negocioId;
    }
}