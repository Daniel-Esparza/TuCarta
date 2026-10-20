<?php

namespace App\Enums;

enum ClavePermiso: string
{
    // Catálogo de negocio (dueño y empleados)
    case VerPedidos = 'ver_pedidos';
    case AvanzarPedidos = 'avanzar_pedidos';
    case MarcarAgotado = 'marcar_agotado';
    case VerMetricas = 'ver_metricas';
    case EditarPrecios = 'editar_precios';
    case EditarPerfil = 'editar_perfil';
    case GestionarEquipo = 'gestionar_equipo';

    // Catálogo de administrador (administrador y moderadores)
    case GestionarNegocios = 'gestionar_negocios';
    case GestionarModeradores = 'gestionar_moderadores';

    public function tipo(): Rol
    {
        return match ($this) {
            self::GestionarNegocios, self::GestionarModeradores => Rol::Administrador,
            default => Rol::Negocio,
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::VerPedidos => 'Ver los pedidos del negocio',
            self::AvanzarPedidos => 'Cambiar el estado de los pedidos',
            self::MarcarAgotado => 'Marcar productos como agotados o disponibles',
            self::VerMetricas => 'Ver las métricas y el ticket promedio',
            self::EditarPrecios => 'Editar productos y precios',
            self::EditarPerfil => 'Editar el perfil del negocio',
            self::GestionarEquipo => 'Dar de alta y desactivar empleados',
            self::GestionarNegocios => 'Administrar negocios de la plataforma',
            self::GestionarModeradores => 'Crear moderadores y asignarles permisos',
        };
    }

    /**
     * @return list<self>
     */
    public static function delCatalogo(Rol $rol): array
    {
        return array_values(array_filter(self::cases(), fn (self $clave) => $clave->tipo() === $rol));
    }
}
