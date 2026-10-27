# Separación por negocio (US-04)

Cada negocio solo ve su propia información. Se aplica en dos capas, como define la arquitectura del Sprint 2:

1. **En el código:** los modelos con `negocio_id` filtran solos por el negocio de la petición.
2. **En la base de datos:** PostgreSQL aplica Row Level Security (RLS). Aunque el código se equivoque, la base no entrega filas de otro negocio.

Este documento es la referencia para conectar las demás historias del Sprint 5 y siguientes.

## Direcciones

El dominio base sale de `TUCARTA_DOMINIO` (`tucarta.localhost` en los escritorios, `tucarta.local` en el servidor).

| Dirección | Para qué |
|---|---|
| `{dominio}` | Landing y páginas de la plataforma. `/perfil` del cliente (CU-06) |
| `auth.{dominio}` | Login unificado (`/`) y panel del administrador y moderadores (`/panel`). No es un negocio |
| `{subdominio}.{dominio}` | Todo lo de un negocio: su página pública (`/`), el seguimiento por folio (`/pedido/{folio}`) y el panel del dueño y sus empleados (`/panel`) |

Subdominios reservados, que nunca son negocio: `auth`, `www`, `admin`, `api`, `app`, `mail` (`config/tucarta.php`).

## Middleware

| Alias | Qué hace | Dónde se usa |
|---|---|---|
| `rls` | Hace que la petición trabaje con el rol `tucarta_app` de PostgreSQL. Ya está en todas las rutas web | Automático |
| `negocio` | Lee el subdominio, busca el negocio y lo fija para toda la petición. Activo: sigue. Suspendido: muestra su aviso. Pendiente, rechazado, reservado o inexistente: la misma página 404 (US-02b) | Grupo de rutas `{subdominio}.{dominio}` |
| `negocio.propio` | Solo deja pasar a usuarios de tipo `negocio` cuyo `negocio_id` sea el del subdominio. A cualquier otro le responde 404 | Rutas del panel del negocio |
| `rol:administrador` | Solo deja pasar al tipo indicado; a los demás les responde 404. Al administrador le permite ver todos los negocios | Rutas de `auth.{dominio}` |

Estos middleware corren **antes** de que Laravel busque registros por la URL (`route model binding`), así que `/panel/categorias/{categoria}` con el id de otro negocio responde 404.

## Reglas para conectar cada historia

### Login unificado

- La pantalla está en `auth.{dominio}/` (ruta `login`, se envía a `login.store`). Para salir: `POST auth.{dominio}/salir` (ruta `logout`). Sin sesión, los paneles mandan ahí.
- Solo entran cuentas con `estado = activo` y contraseña definida. Se guarda `last_login`.
- Después de entrar:
  - `administrador` → `auth.{dominio}/panel` (ruta `panel.administrador`).
  - `negocio` → `{su subdominio}.{dominio}/panel` (ruta `panel.negocio`).
  - `cliente` → al dominio principal (`{dominio}/perfil` cuando exista).
- Tras 5 intentos fallidos con el mismo correo, se bloquea 5 minutos.
- La sesión vale en todos los subdominios sin tocar el `.env` (decisión D4, middleware `CompartirSesion`): si se entra por `{dominio}` o un subdominio, la cookie se emite para `.{dominio}`. Si se entra por `localhost`, queda solo en esa dirección. Un `SESSION_DOMAIN` en el `.env` tiene prioridad.
- Los tipos base son `administrador`, `negocio` y `cliente`. El dueño es `negocio`, no "restaurante".

### US-23 Empleados

- Un empleado es un usuario `negocio` con el `negocio_id` de su dueño.
- Sus permisos se dan con `otorgarPermiso()` y solo del catálogo de negocio. Para el empleado: `avanzar_pedidos` y `marcar_agotado`.
- Sus rutas van dentro del grupo del subdominio con `auth` y `negocio.propio`, más `permiso:{clave}` para cada acción. La pantalla de equipo está en `{subdominio}.{dominio}/panel/equipo` y pide `gestionar_equipo`.

### US-27 Moderadores

- Un moderador es un usuario `administrador` sin `negocio_id`.
- Un permiso con negocio vacío es global; con negocio, aplica solo a ese negocio.
- Sus rutas van en `auth.{dominio}` con `auth` y `rol:administrador`. La pantalla está en `auth.{dominio}/panel/moderadores`. Las Policies revisan `$usuario->tienePermiso(ClavePermiso::GestionarNegocios, $negocio)`.
- Los permisos se asignan con `otorgarPermiso()`, que rechaza claves de otro catálogo.

### Permisos en general

- Siempre con `ClavePermiso`, nunca con texto: `$usuario->tienePermiso(ClavePermiso::VerPedidos)`.
- Una clave nueva se agrega al enum (con su catálogo y descripción) y `PermisosSeeder` la carga sola.
- En las rutas: `->middleware('permiso:ver_pedidos')`; si la clave no existe en el enum, la ruta falla desde el inicio.

### Modelos nuevos con `negocio_id` (pedidos y los que vengan)

```php
use App\Models\Concerns\PerteneceANegocio;

class Pedido extends Model
{
    use PerteneceANegocio;
}
```

- Las consultas se filtran solas por el negocio actual.
- Al crear, `negocio_id` se toma del negocio actual, nunca del formulario.
- Si la tabla es nueva, se agrega a RLS en su migración:

```php
use App\Support\SeguridadPorFilas;

SeguridadPorFilas::activar('mi_tabla');    // en up()
SeguridadPorFilas::desactivar('mi_tabla'); // en down()
```

### Pruebas de cualquier historia

- Con `actingAs()` y factories, sin depender del login.
- Las peticiones se hacen a la dirección completa: `$this->get('http://tacos-juan.'.config('tucarta.dominio').'/panel')`.
- Si se usan permisos: `$this->seed(PermisosSeeder::class)`.
- Para probar un modelo con un negocio fijado: `app(NegocioActual::class)->establecer($negocio)`.

## Cómo funciona por dentro

- **Tablas con RLS** (forzado): `categorias`, `horarios`, `items`, `grupos_opciones`, `opciones`, `pedidos`, `pedido_items`, `pedido_item_opciones` y `pedido_estado_historial`.
- **Sin RLS:** `negocios`, `usuarios`, `permisos` y `usuario_permisos`. Se consultan antes de saber de qué negocio se trata (subdominio y login); ahí el filtro lo aplica el código.
- **El rol `tucarta_app`** no es superusuario ni puede saltarse RLS. Hace falta porque el usuario de Docker (`sail`) es superusuario, y PostgreSQL no le aplica RLS a los superusuarios. La conexión cambia a ese rol al inicio de cada petición web (`SET ROLE`), así que no hace falta otra contraseña en el `.env` y las migraciones siguen corriendo con el usuario normal.
- **El negocio de la petición** se pasa a PostgreSQL en `app.negocio_id`. Sin negocio fijado, las tablas con RLS no entregan ninguna fila. El administrador usa `app.ver_todo`.
- **Comandos de Artisan, seeders y colas** no pasan por el middleware: trabajan con el usuario normal, sin RLS, y el filtro de Eloquent solo aplica si se fija un negocio.

## Demostración en DBeaver

Conectado como `sail`, ejecuta una por una:

```sql
SET ROLE tucarta_app;
SELECT set_config('app.negocio_id', (SELECT id::text FROM negocios WHERE subdominio = 'pizza-nova'), false);
SELECT negocio_id, nombre FROM categorias;   -- solo las de Pizza Nova
SELECT set_config('app.negocio_id', '', false);
SELECT count(*) FROM categorias;             -- 0: sin negocio no hay filas
RESET ROLE;
SELECT count(*) FROM categorias;             -- todas: sail es superusuario
```
