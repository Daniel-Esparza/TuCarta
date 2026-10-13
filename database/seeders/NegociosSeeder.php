<?php

namespace Database\Seeders;

use App\Models\Negocio;
use Illuminate\Database\Seeder;

class NegociosSeeder extends Seeder
{
    public function run(): void
    {

        // negocio de pizza 
        
        $pizzeria = Negocio::create([
            'nombre'           => 'Pizza Nova',
            'subdominio'        => 'pizza-nova',
            'descripcion'      => 'Pizzas de las normales',
            'direccion'        => 'Av. Universidad 1234, San Luis Potosi',
            'telefono_whatsapp'         => '524441234567',
            'zona_horaria'     => 'America/Mexico_City',
            'categoria'         => 'Pizzería',    
            'cerrado_temporal' => false,
            'estado'            => 'activo',
            'correo_contacto'   => 'contacto@pizzanova.mx',
        ]);

        // Este apartado pertenece al horario
        foreach (range(1, 7) as $dia) {
            $pizzeria->horarios()->create([
                'dia_semana'    => $dia,
                'hora_apertura' => '12:00:00',
                'hora_cierre'   => '22:00:00',
            ]);
        }

        // Categorias 
        $catPizzas  = $pizzeria->categorias()->create(['nombre' => 'Pizzas',  'orden' => 1]);
        $catBebidas = $pizzeria->categorias()->create(['nombre' => 'Bebidas', 'orden' => 2]);

        // items
        $margarita = $catPizzas->items()->create([
            'nombre'      => 'Pizza Margarita',
            'descripcion' => 'Salsa de tomate San Marzano, mozzarella fresca y albahaca.',
            'precio'      => 149.00,
        ]);

        $catPizzas->items()->create([
            'nombre'      => 'Pizza Pepperoni',
            'descripcion' => 'Doble pepperoni y queso mozzarella extra.',
            'precio'      => 179.00,
        ]);

        $catBebidas->items()->create([
            'nombre'      => 'Refresco 600 ml',
            'descripcion' => 'Coca-Cola, Sprite o Fanta.',
            'precio'      => 35.00,
        ]);

        // Grupo de opciones 
        $tamano = $margarita->gruposOpciones()->create([
            'nombre'          => 'Tamaño',
            'es_multiple'     => false,   
            'es_obligatorio'  => true,    
            'min_selecciones' => 1,
            'max_selecciones' => 1,
            'orden'           => 1,
        ]);

        $tamano->opciones()->createMany([
            ['nombre' => 'Chica (8 rebanadas)',    'incremento_precio' => 0.00,  'orden' => 1],
            ['nombre' => 'Mediana (12 rebanadas)', 'incremento_precio' => 45.00, 'orden' => 2],
            ['nombre' => 'Grande (16 rebanadas)',  'incremento_precio' => 85.00, 'orden' => 3],
        ]);

        // negocio de comida china (se me antojo)

        $china = Negocio::create([
            'nombre'           => 'Dragon ball Z',
            'subdominio'        => 'Dragon z',    
            'descripcion'      => 'comida china de la buena',
            'direccion'        => 'Calle Himno Nacional 456, San Luis Potosi',
            'telefono_whatsapp'         => '524441234567',
            'correo_contacto'   => 'contacto@china.mx',
            'categoria'         => 'comida China',  
            'estado'            => 'activo',
            'zona_horaria'     => 'America/Mexico_City',
            'cerrado_temporal' => false,
        ]);

        // Este apartado pertenece al horario 
        foreach (range(2, 7) as $dia) {
            $china->horarios()->create([
                'dia_semana'    => $dia,
                'hora_apertura' => '13:00:00',
                'hora_cierre'   => '21:00:00',
            ]);
        }

        // Categorias
        $catEntradas = $china->categorias()->create(['nombre' => 'Entradas',          'orden' => 1]);
        $catFuertes  = $china->categorias()->create(['nombre' => 'items fuertes', 'orden' => 2]);
        $catBebidas2 = $china->categorias()->create(['nombre' => 'Bebidas',           'orden' => 3]);

        // items
        $catEntradas->items()->create([
            'nombre'      => 'Rollitos de primavera (6 pzs)',
            'descripcion' => 'Relleno de verdura fresca, con salsa agridulce.',
            'precio'      => 65.00,
        ]);

        $catFuertes->items()->create([
            'nombre'      => 'Pollo agridulce',
            'descripcion' => 'Pollo empanizado con salsa agridulce, piña y pimiento.',
            'precio'      => 120.00,
        ]);

        // Este apartado sirve para ver cuando un platillo este agotado
        $catFuertes->items()->create([
            'nombre'         => 'Arroz frito con camaron',
            'descripcion'    => 'Arroz salteado al wok con camaron, huevo y verduras.',
            'precio'         => 135.00,
            'disponible_hoy' => false,
        ]);

        $catBebidas2->items()->create([
            'nombre'      => 'Te de jazmin (jarra)',
            'descripcion' => 'Te verde aromatizado con flor de jazmin.',
            'precio'      => 40.00,
        ]);
    }
}