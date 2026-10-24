<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Database\Seeder;

class PermisosAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $claves = [
            'gestionar_negocios' => 'Ver y administrar negocios',
            'gestionar_moderadores' => 'Crear moderadores y asignarles permisos',
        ];

        $admin = Usuario::where('email', 'admin@tucarta.local')->first();

        foreach ($claves as $clave => $descripcion) {
            $permiso = Permiso::firstOrCreate(
                ['clave' => $clave],
                ['descripcion' => $descripcion, 'tipo_usuario' => 'administrador']
            );

            // El administrador principal tiene ambos con alcance global.
            if ($admin) {
                UsuarioPermiso::firstOrCreate([
                    'usuario_id' => $admin->id,
                    'permiso_id' => $permiso->id,
                    'negocio_id' => null,
                ]);
            }
        }
        // moderador@tucarta.local queda sin permisos, a propósito.
    }
}