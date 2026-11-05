# Desarrollo

Guía para quien programa en el proyecto. Para solo ejecutarlo y revisarlo, basta con el [README](../README.md).

## Instalar con Sail

Estos comandos son de Linux: en Windows van **dentro de WSL2**, con el proyecto en una carpeta de WSL (por ejemplo `~/proyectos`). No funcionan en PowerShell ni en CMD.

```bash
git clone https://github.com/Daniel-Esparza/TuCarta.git
cd TuCarta
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" -w /app composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Si sale un error de conexión a `pgsql`, espera unos segundos a que PostgreSQL termine de arrancar y vuelve a correr el comando.

## Comandos útiles

| Qué quieres hacer | Comando |
|---|---|
| Levantar el proyecto | `./vendor/bin/sail up -d` |
| Apagarlo | `./vendor/bin/sail down` |
| Recrear la base con datos de prueba | `./vendor/bin/sail artisan migrate:fresh --seed` |
| Correr las pruebas | `./vendor/bin/sail artisan test` |
| Compilar los estilos | `./vendor/bin/sail npm run build` |
| Ver las rutas | `./vendor/bin/sail artisan route:list --except-vendor` |
| Abrir una terminal dentro del contenedor | `./vendor/bin/sail shell` |

Las pruebas corren en PostgreSQL, no en SQLite: la separación por negocio usa Row Level Security.

## Permisos

El catálogo vive en `App\Enums\ClavePermiso` y se carga con el seeder. Cada permiso pertenece a un catálogo: una cuenta de negocio nunca puede recibir un permiso de administrador, ni al revés. Un permiso asignado con `negocio_id` vacío es global y aplica a todos los negocios, incluidos los que se creen después.

| Clave | Catálogo | Qué permite |
|---|---|---|
| `ver_pedidos` | negocio | Ver los pedidos del negocio |
| `avanzar_pedidos` | negocio | Cambiar el estado de los pedidos |
| `marcar_agotado` | negocio | Marcar productos como agotados o disponibles |
| `ver_metricas` | negocio | Ver las métricas y el ticket promedio |
| `editar_precios` | negocio | Editar productos y precios |
| `editar_perfil` | negocio | Editar el perfil del negocio |
| `gestionar_equipo` | negocio | Dar de alta y desactivar empleados |
| `gestionar_negocios` | administrador | Administrar negocios de la plataforma |
| `gestionar_moderadores` | administrador | Crear moderadores y asignarles permisos |

En el código siempre con el enum, nunca con texto:

```php
use App\Enums\ClavePermiso;

$usuario->tienePermiso(ClavePermiso::VerPedidos);              // en su propio negocio
$usuario->tienePermiso(ClavePermiso::GestionarNegocios, $negocio);
$usuario->tienePermisoGlobal(ClavePermiso::GestionarModeradores);
$empleado->otorgarPermiso(ClavePermiso::MarcarAgotado, $negocio, $dueno);
$empleado->quitarPermiso(ClavePermiso::MarcarAgotado, $negocio);
$dueno->otorgarPermisosDeDueno();                              // los siete de su negocio
```

En las rutas: `->middleware('permiso:marcar_agotado')`. La clave tiene que existir en el enum.

## Flujo de trabajo con Git (Git Flow)

- `main`: versión estable, lo que se entrega.
- `dev`: rama de integración, donde se juntan todas las historias.
- `feature/US-XX-descripcion-corta`: una rama por historia de usuario, siempre creada desde `dev`.

Reglas:

1. Nadie sube directo a `main` ni a `dev`.
2. Cada rama `feature/*` entra a `dev` por Pull Request.
3. Todo Pull Request necesita la revisión de otra persona del equipo (nadie revisa el suyo).
4. Antes de pedir revisión: corre `migrate:fresh --seed` y `artisan test` en tu rama.
5. Una historia está terminada cuando otra persona la probó y funciona en el servidor del equipo, no solo en tu computadora.

## Más documentación

- [servidor.md](servidor.md): dominio, HTTPS con Traefik y mkcert, y los pasos para el servidor del equipo.
- [seguridad-por-negocio.md](seguridad-por-negocio.md): separación por negocio, RLS y reglas para conectar las demás historias.
- [kit-diseno.md](kit-diseno.md): componentes de la interfaz.
