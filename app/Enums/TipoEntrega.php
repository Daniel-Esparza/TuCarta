<?php

namespace App\Enums;

// Forma de entrega del pedido (US-20).
enum TipoEntrega: string
{
    case EnSitio = 'en_sitio';
    case ParaLlevar = 'para_llevar';
    case Domicilio = 'domicilio';
}
