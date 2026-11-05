<?php

namespace Database\Seeders;

use App\Enums\ClavePermiso;
use App\Models\Permiso;
use Illuminate\Database\Seeder;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ClavePermiso::cases() as $clave) {
            Permiso::updateOrCreate(
                ['clave' => $clave->value],
                ['descripcion' => $clave->descripcion(), 'tipo_usuario' => $clave->tipo()->value],
            );
        }
    }
}
