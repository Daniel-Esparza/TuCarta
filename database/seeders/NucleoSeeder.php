<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class NucleoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermisosSeeder::class);

        // Contraseña de todas las cuentas: password
        Usuario::factory()->administrador()->create([
            'nombre' => 'Admin',
            'apellidos' => 'TuCarta',
            'email' => 'admin@tucarta.local',
        ]);

        // Moderador sin permisos
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

        // Cada dueño recibe todos los permisos de su negocio
        Usuario::factory()->deNegocio($tacos)->create([
            'nombre' => 'Juan',
            'email' => 'juan@tacos-juan.tucarta.local',
        ])->otorgarPermisosDeDueno();

        Usuario::factory()->deNegocio($sushi)->create([
            'nombre' => 'Ana',
            'email' => 'ana@sushi-ana.tucarta.local',
        ])->otorgarPermisosDeDueno();
    }
}
