<?php

namespace App\Support;

/**
 * Arma direcciones del dominio principal o de un subdominio, con el mismo
 * esquema y puerto de la petición actual.
 */
class Dominio
{
    public static function url(?string $subdominio = null, string $ruta = '/'): string
    {
        $request = request();
        $puerto = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();
        $host = ($subdominio ? $subdominio.'.' : '').config('tucarta.dominio');

        return $request->getScheme().'://'.$host.$puerto.$ruta;
    }
}
