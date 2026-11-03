<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Database\Seeder;


class PermisosNegocioSeeder extends Seeder
{
    public function run(): void
    {
        $claves = [
            'gestionar_equipo' => 'Dar de alta y desactivar empleados',
            'editar_perfil' => 'Editar los datos del negocio',
            'avanzar_pedidos' => 'Avanzar el estado de los pedidos',
            'marcar_agotado' => 'Marcar productos como agotados',
            'ver_metricas' => 'Ver las metricas del negocio',
            'editar_precios' => 'Editar los precios del menu',
        ];

        $permisos = [];
        foreach ($claves as $clave => $descripcion) {
            $permisos[] = Permiso::firstOrCreate(
                ['clave' => $clave],
                ['descripcion' => $descripcion, 'tipo_usuario' => 'negocio']
            );
        }


        Usuario::where('rol', Rol::Negocio->value)
            ->whereNotNull('negocio_id')
            ->get()
            ->each(function (Usuario $dueno) use ($permisos): void {
                foreach ($permisos as $permiso) {
                    UsuarioPermiso::firstOrCreate([
                        'usuario_id' => $dueno->id,
                        'permiso_id' => $permiso->id,
                        'negocio_id' => $dueno->negocio_id,
                    ]);
                }
            });
    }
}