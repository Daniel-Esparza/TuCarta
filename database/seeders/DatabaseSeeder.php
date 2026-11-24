<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([

            NucleoSeeder::class,
            PermisosAdministradorSeeder::class,
            NegociosSeeder::class,
            EmpleadosSeeder::class,
            MenusDePruebaSeeder::class,

        ]);

        // Pedidos de demostración. Las pruebas no los cargan porque cuentan sus propios pedidos.
        if (! app()->environment('testing')) {
            $this->call(PedidosDePruebaSeeder::class);
        }

    }
}
