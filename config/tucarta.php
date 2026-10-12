<?php

return [

    /*
    | Dominio base de la plataforma. Cada negocio se abre en
    | {subdominio}.{dominio}. Es lo único que cambia entre un escritorio
    | local (tucarta.localhost) y el servidor del equipo (tucarta.local).
    */

    'dominio' => env('TUCARTA_DOMINIO', 'tucarta.localhost'),

];
