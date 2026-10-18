<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Cuentas y negocios base para desarrollo. La contraseña de todas las
 * cuentas es la del factory ("password").
 */
class NucleoSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::factory()->administrador()->create([
            'nombre' => 'Admin',
            'apellidos' => 'TuCarta',
            'email' => 'admin@tucarta.local',
        ]);

        // Mismo tipo base que el administrador, pero sin ningún permiso asignado.
        Usuario::factory()->administrador()->create([
            'nombre' => 'Moderador',
            'apellidos' => 'Sin Permisos',
            'email' => 'moderador@tucarta.local',
        ]);

        $tacos = Negocio::factory()->create([
            'nombre' => 'Tacos Juan',
            'subdominio' => 'tacos-juan',
        ]);

        $sushi = Negocio::factory()->create([
            'nombre' => 'Sushi Ana',
            'subdominio' => 'sushi-ana',
        ]);

        Usuario::factory()->deNegocio($tacos)->create([
            'nombre' => 'Juan',
            'email' => 'juan@tacos-juan.tucarta.local',
        ]);

        Usuario::factory()->deNegocio($sushi)->create([
            'nombre' => 'Ana',
            'email' => 'ana@sushi-ana.tucarta.local',
        ]);
    }
}
