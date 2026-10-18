<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El layout carga CSS y JS con @vite; en pruebas no hace falta compilarlos.
        $this->withoutVite();
    }

    public function test_la_pantalla_de_login_se_muestra(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_login_correcto_lleva_al_panel(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('clave-correcta-1')]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'clave-correcta-1',
        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_con_contrasena_incorrecta_muestra_error_general(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('clave-correcta-1')]);

        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'clave-equivocada',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Las credenciales no son correctas.']);

        $this->assertGuest();
    }

    public function test_el_error_es_igual_si_el_correo_no_existe(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'no-existe@ejemplo.com',
            'password' => 'cualquier-clave',
        ])->assertSessionHasErrors(['email' => 'Las credenciales no son correctas.']);

        $this->assertGuest();
    }

    public function test_campos_vacios_muestran_mensajes_en_espanol(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertSessionHasErrors([
                'email' => 'Escribe tu correo electrónico.',
                'password' => 'Escribe tu contraseña.',
            ]);
    }

    public function test_se_bloquea_el_acceso_tras_5_intentos_fallidos(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('clave-correcta-1')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'clave-equivocada',
            ]);
        }

        // Aunque ahora la clave sea correcta, sigue bloqueado.
        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'clave-correcta-1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Demasiados intentos fallidos',
            session('errors')->first('email')
        );
    }

    public function test_el_panel_sin_sesion_redirige_al_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
    }

    public function test_con_sesion_se_ve_el_panel(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/panel')
            ->assertOk()
            ->assertSee('Cerrar sesión');
    }

    public function test_cerrar_sesion_vuelve_al_login(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
