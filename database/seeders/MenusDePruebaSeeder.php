<?php

namespace Database\Seeders;

use App\Models\Negocio;
use Illuminate\Database\Seeder;

/**
 * Menús de Tacos Juan y Sushi Ana, los negocios con cuenta de dueño.
 * Sirven para ver dos negocios distintos, cada uno en su dirección.
 */
class MenusDePruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->cargar('tacos-juan', [
            'descripcion' => 'Taquería familiar con tacos al pastor y de suadero.',
            'direccion' => 'Av. Carranza 815, San Luis Potosí',
            'categoria' => 'Tacos',
        ], [[2, 3, 4, 5, 6, 7], '18:00:00', '01:00:00'], [
            'Tacos' => [
                ['Taco al pastor', 'Con piña, cebolla y cilantro.', 22],
                ['Taco de suadero', 'En tortilla de maíz hecha a mano.', 22],
                ['Gringa', 'Tortilla de harina con pastor y queso.', 55],
            ],
            'Bebidas' => [
                ['Agua de horchata', 'Vaso de 500 ml.', 30],
                ['Agua de jamaica', 'Vaso de 500 ml.', 30, false],
            ],
        ], ['Taco al pastor', 'Extras', [['Queso', 10], ['Piña extra', 5]]]);

        $this->cargar('sushi-ana', [
            'descripcion' => 'Sushi y cocina japonesa para llevar.',
            'direccion' => 'Calle Arista 230, San Luis Potosí',
            'categoria' => 'Sushi',
        ], [[1, 2, 3, 4, 5, 6], '13:00:00', '22:00:00'], [
            'Rollos' => [
                ['Rollo California', 'Surimi, pepino y aguacate.', 120],
                ['Rollo Philadelphia', 'Salmón, queso crema y pepino.', 135],
                ['Rollo empanizado', 'Camarón, queso crema y salsa de anguila.', 145],
            ],
            'Entradas' => [
                ['Gyozas (6 piezas)', 'Rellenas de cerdo y verdura.', 85],
            ],
            'Bebidas' => [
                ['Té verde', 'Jarra de 1 litro.', 45],
            ],
        ], ['Rollo California', 'Salsas', [['Anguila', 0], ['Picante', 0], ['Soya extra', 5]]]);
    }

    private function cargar(string $subdominio, array $perfil, array $horario, array $menu, array $opciones): void
    {
        $negocio = Negocio::where('subdominio', $subdominio)->first();

        if ($negocio === null || $negocio->categorias()->exists()) {
            return;
        }

        $negocio->update($perfil);

        [$dias, $apertura, $cierre] = $horario;
        foreach ($dias as $dia) {
            $negocio->horarios()->create([
                'negocio_id' => $negocio->id,
                'dia_semana' => $dia,
                'hora_apertura' => $apertura,
                'hora_cierre' => $cierre,
            ]);
        }

        $orden = 0;
        $items = [];
        foreach ($menu as $categoriaNombre => $productos) {
            $categoria = $negocio->categorias()->create([
                'negocio_id' => $negocio->id,
                'nombre' => $categoriaNombre,
                'orden' => $orden++,
            ]);

            foreach ($productos as $producto) {
                [$nombre, $descripcion, $precio] = $producto;

                $items[$nombre] = $categoria->items()->create([
                    'negocio_id' => $negocio->id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'disponible_hoy' => $producto[3] ?? true,
                ]);
            }
        }

        [$producto, $grupoNombre, $lista] = $opciones;
        $grupo = $items[$producto]->gruposOpciones()->create([
            'negocio_id' => $negocio->id,
            'nombre' => $grupoNombre,
            'es_multiple' => true,
            'es_obligatorio' => false,
            'min_selecciones' => 0,
            'max_selecciones' => count($lista),
        ]);

        foreach ($lista as $i => [$nombre, $precio]) {
            $grupo->opciones()->create([
                'negocio_id' => $negocio->id,
                'nombre' => $nombre,
                'incremento_precio' => $precio,
                'orden' => $i,
            ]);
        }
    }
}
