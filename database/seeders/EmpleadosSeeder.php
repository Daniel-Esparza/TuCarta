<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Permiso;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class EmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $avanzarPedidos = Permiso::firstOrCreate(
            ['clave' => 'avanzar_pedidos'],
            ['descripcion' => 'Avanzar el estado de los pedidos', 'tipo_usuario' => 'negocio'],
        );

        $marcarAgotado = Permiso::firstOrCreate(
            ['clave' => 'marcar_agotado'],
            ['descripcion' => 'Marcar productos como agotados', 'tipo_usuario' => 'negocio'],
        );

        $tacos = Negocio::where('subdominio', 'tacos-juan')->first();

        if ($tacos === null) {
            return;
        }

        $empleado = Usuario::factory()->deNegocio($tacos)->create([
            'nombre' => 'Marco',
            'apellidos' => 'El del Mostrador',
            'email' => 'marco@tacos-juan.tucarta.local',
        ]);

        $empleado->permisos()->createMany([
            ['permiso_id' => $avanzarPedidos->id, 'negocio_id' => $tacos->id],
            ['permiso_id' => $marcarAgotado->id, 'negocio_id' => $tacos->id],
        ]);
    }
}