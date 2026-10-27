<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_la_landing_page_carga_y_explica_tucarta(): void
    {
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('TuCarta');
        $respuesta->assertSee('Cómo funciona');
        $respuesta->assertSee('WhatsApp');
        $respuesta->assertSee('Registrar mi negocio');
    }

    public function test_tiene_las_secciones_de_la_historia_us_31(): void
    {
        $this->get('/')
            ->assertSeeInOrder([
                'Consulta la carta de tu restaurante y haz tu pedido por WhatsApp',
                'Encuentra un restaurante',
                'Arma tu pedido',
                'Continúa por WhatsApp',
                '¿Tienes un restaurante? Presenta tu carta en TuCarta',
                'Conoce TuCarta',
            ]);
    }

    public function test_enlaza_al_auto_registro_y_al_login(): void
    {
        $this->get('http://'.config('tucarta.dominio').'/')
            ->assertSee('href="http://'.config('tucarta.dominio').'/registrar-negocio"', false)
            ->assertSee('href="http://auth.'.config('tucarta.dominio').'"', false)
            ->assertSee('href="#como-funciona"', false);
    }
}
