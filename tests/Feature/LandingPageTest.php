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
}
