<?php

namespace App\Enums;

// Forma de entrega del pedido (US-20).
enum TipoEntrega: string
{
    case EnSitio = 'en_sitio';
    case ParaLlevar = 'para_llevar';
    case Domicilio = 'domicilio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnSitio => 'En el lugar',
            self::ParaLlevar => 'Para llevar',
            self::Domicilio => 'A domicilio',
        };
    }
}
