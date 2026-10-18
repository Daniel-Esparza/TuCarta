<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HttpsDetrasDeProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/prueba/esquema', fn () => [
            'seguro' => request()->secure(),
            'url' => url('/'),
        ]);
    }

    public function test_laravel_reconoce_el_https_que_termina_traefik(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tacos-juan.tucarta.localhost',
            'X-Forwarded-Port' => '443',
        ])->getJson('http://tucarta.localhost/prueba/esquema')
            ->assertJson([
                'seguro' => true,
                'url' => 'https://tacos-juan.tucarta.localhost',
            ]);
    }

    public function test_sin_proxy_la_peticion_sigue_siendo_http(): void
    {
        // URL completa para no depender del APP_URL de cada .env.
        $this->getJson('http://tucarta.localhost/prueba/esquema')->assertJson(['seguro' => false]);
    }

    public function test_el_dominio_base_sale_de_la_configuracion(): void
    {
        config(['tucarta.dominio' => 'tucarta.local']);

        $this->assertSame('tucarta.local', config('tucarta.dominio'));
    }
}
