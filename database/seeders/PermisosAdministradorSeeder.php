<?php

namespace Database\Seeders;

use App\Enums\ClavePermiso;
use App\Enums\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class PermisosAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermisosSeeder::class);

        $admin = Usuario::where('email', 'admin@tucarta.local')->first();

        // El administrador principal tiene todos los de su catálogo con alcance global.
        if ($admin) {
            foreach (ClavePermiso::delCatalogo(Rol::Administrador) as $clave) {
                $admin->otorgarPermiso($clave);
            }
        }
        // moderador@tucarta.local queda sin permisos, a propósito.
    }
}
