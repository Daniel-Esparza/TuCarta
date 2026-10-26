<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Pruebas del componente x-select del kit de diseno (US-34). */
class SelectKitTest extends TestCase
{
    public function test_el_select_muestra_etiqueta_opciones_y_error(): void
    {
        $this->withViewErrors(['estado' => 'Estado no valido.'])
            ->blade('<x-select nombre="estado" etiqueta="Estado"><option value="activo">Activo</option></x-select>')
            ->assertSee('Estado')
            ->assertSee('<option value="activo">Activo</option>', false)
            ->assertSee('Estado no valido.')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_el_select_puede_ocultar_su_error(): void
    {
        $this->withViewErrors(['estado' => 'Estado no valido.'])
            ->blade('<x-select nombre="estado" etiqueta="Estado" :mostrar-error="false"><option value="a">A</option></x-select>')
            ->assertDontSee('Estado no valido.')
            ->assertSee('aria-invalid="false"', false);
    }

    public function test_el_select_usa_el_id_que_se_le_da(): void
    {
        $this->blade('<x-select nombre="permiso_id" etiqueta="Permiso" id="permiso-7"><option value="1">uno</option></x-select>')
            ->assertSee('id="permiso-7"', false)
            ->assertSee('for="permiso-7"', false);
    }
}
