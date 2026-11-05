<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Row Level Security de PostgreSQL para las tablas con negocio_id.
 *
 * Las políticas solo aplican con el rol tucarta_app: el usuario de Docker es
 * superusuario y PostgreSQL no le aplica RLS.
 */
class SeguridadPorFilas
{
    public const ROL = 'tucarta_app';

    public static function activar(string $tabla): void
    {
        $tabla = self::validarNombre($tabla);
        $politica = "{$tabla}_por_negocio";
        $condicion = "current_setting('app.ver_todo', true) = 'on'"
            ." OR negocio_id = nullif(current_setting('app.negocio_id', true), '')::bigint";

        DB::statement("ALTER TABLE {$tabla} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$tabla} FORCE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS {$politica} ON {$tabla}");
        DB::statement("CREATE POLICY {$politica} ON {$tabla} USING ({$condicion}) WITH CHECK ({$condicion})");
    }

    public static function desactivar(string $tabla): void
    {
        $tabla = self::validarNombre($tabla);

        DB::statement("DROP POLICY IF EXISTS {$tabla}_por_negocio ON {$tabla}");
        DB::statement("ALTER TABLE {$tabla} NO FORCE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$tabla} DISABLE ROW LEVEL SECURITY");
    }

    public static function usarRolDeAplicacion(): void
    {
        DB::statement('SET ROLE '.self::ROL);
    }

    public static function restablecer(): void
    {
        DB::statement('RESET ROLE');
        self::fijarNegocio(null);
    }

    public static function fijarNegocio(?int $negocioId): void
    {
        DB::select("select set_config('app.negocio_id', ?, false)", [(string) $negocioId]);
        DB::select("select set_config('app.ver_todo', 'off', false)");
    }

    public static function verTodo(): void
    {
        DB::select("select set_config('app.negocio_id', '', false)");
        DB::select("select set_config('app.ver_todo', 'on', false)");
    }

    private static function validarNombre(string $tabla): string
    {
        if (! preg_match('/^[a-z_][a-z0-9_]*$/', $tabla)) {
            throw new InvalidArgumentException("Nombre de tabla no válido: {$tabla}");
        }

        return $tabla;
    }
}
