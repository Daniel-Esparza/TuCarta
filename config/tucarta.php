<?php

return [

    // Cada negocio se abre en {subdominio}.{dominio}
    'dominio' => env('TUCARTA_DOMINIO', 'tucarta.localhost'),

    // Subdominios que nunca son un negocio (US-02)
    'reservados' => ['auth', 'www', 'admin', 'api', 'app', 'mail'],

];
