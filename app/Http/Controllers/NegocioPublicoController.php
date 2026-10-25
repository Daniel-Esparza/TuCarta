<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Symfony\Component\HttpFoundation\Response;

class NegocioPublicoController extends Controller
{
    // El panel central vive en auth.{dominio} y no es un negocio.
    private const SUBDOMINIO_CENTRAL = 'auth';

    /**
     * US-02b: la direccion del negocio responde segun su estado.
     * Pendiente, rechazado e inexistente muestran la misma pagina,
     * para no revelar cual de los tres es.
     */
    public function mostrar(string $subdominio): Response
    {
        if ($subdominio === self::SUBDOMINIO_CENTRAL) {
            return redirect()->route('panel');
        }

        $negocio = Negocio::where('subdominio', $subdominio)->first();

        return match ($negocio?->estado) {
            'activo' => response()->view('negocio.provisional', ['negocio' => $negocio]),
            'suspendido' => response()->view('negocio.suspendido', ['negocio' => $negocio]),
            default => response()->view('negocio.no-disponible', [], 404),
        };
    }
}