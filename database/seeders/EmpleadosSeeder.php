<?php

namespace Database\Seeders;

use App\Enums\ClavePermiso;
use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class EmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermisosSeeder::class);

        $tacos = Negocio::where('subdominio', 'tacos-juan')->first();

        if ($tacos === null) {
            return;
        }

        $empleado = Usuario::factory()->deNegocio($tacos)->create([
            'nombre' => 'Marco',
            'apellidos' => 'El del Mostrador',
            'email' => 'marco@tacos-juan.tucarta.local',
        ]);

        $empleado->otorgarPermiso(ClavePermiso::AvanzarPedidos, $tacos);
        $empleado->otorgarPermiso(ClavePermiso::MarcarAgotado, $tacos);
    }
}
