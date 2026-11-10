# TuCarta

Plataforma de catálogos digitales para restaurantes y negocios pequeños: cada negocio tiene su menú en su propia dirección web y recibe los pedidos por WhatsApp. TuCarta no cobra ni procesa pagos.

Proyecto de la materia Arquitectura Avanzada de Aplicaciones Web (UTSLP).

## Ejecutar el proyecto

Solo necesitas **Git** y **Docker Desktop abierto**. No hace falta instalar PHP, Composer, Node ni PostgreSQL. Estos comandos funcionan igual en PowerShell, CMD, macOS y Linux:

```
git clone https://github.com/Daniel-Esparza/TuCarta.git
cd TuCarta
docker compose up -d
```

**La primera vez tarda alrededor de una hora**, según la computadora y la conexión: construye la imagen, instala las dependencias, compila los estilos, crea la configuración y carga la base con datos de prueba. Las siguientes veces arranca en segundos. Para ver el avance:

```
docker compose logs -f laravel.test
```

Cuando aparezca `Server running on [http://0.0.0.0:80]`, sal con `Ctrl+C` y abre **http://tucarta.localhost** en Chrome, Edge o Firefox.

Usa esa dirección y no `localhost`: las sesiones se comparten entre los subdominios de `tucarta.localhost`. No hay que editar el archivo `hosts`, porque los navegadores ya resuelven `*.localhost` hacia tu computadora.

### Si algo falla

| Qué pasa | Qué hacer |
|---|---|
| El puerto 80 está ocupado | Apaga con `docker compose down` y levanta en otro puerto. PowerShell: `$env:APP_PORT=8080; docker compose up -d`. CMD: `set APP_PORT=8080` y luego `docker compose up -d`. macOS o Linux: `APP_PORT=8080 docker compose up -d`. Después abre http://tucarta.localhost:8080 |
| El puerto 5432 está ocupado | Igual que el anterior, con la variable `FORWARD_DB_PORT` (por ejemplo `5433`) |
| Ya lo habías intentado antes | Empieza limpio: en esa carpeta corre `docker compose down -v`, borra la carpeta y vuelve a clonar |
| `docker` no se reconoce o no conecta | Abre Docker Desktop y espera a que diga que está corriendo |
| Las páginas cargan lento en Windows | Es normal si la carpeta está en el disco de Windows. Si tienes WSL, clona dentro de una carpeta de Ubuntu |

Para apagarlo: `docker compose down`. Para borrar también la base de datos: `docker compose down -v`.

## Cuentas de prueba

La contraseña de todas es `password`. Se inicia sesión en http://auth.tucarta.localhost y cada cuenta llega a su panel.

| Correo | Tipo | Qué puede hacer |
|---|---|---|
| `admin@tucarta.local` | Administrador | Todo el panel central |
| `moderador@tucarta.local` | Moderador | Solo lo que le asigne el administrador; empieza sin permisos |
| `juan@tacos-juan.tucarta.local` | Dueño de Tacos Juan | Todo el panel de su negocio |
| `ana@sushi-ana.tucarta.local` | Dueña de Sushi Ana | Todo el panel de su negocio |
| `marco@tacos-juan.tucarta.local` | Empleado de Tacos Juan | Avanzar pedidos y marcar agotados |

Negocios de prueba: `tacos-juan`, `sushi-ana`, `pizza-nova` (con carta, opciones y horario) y `dragon-z` (con carta, un producto agotado y horario).

## Recorrido sugerido

1. **Público:** abre http://tucarta.localhost y registra un negocio con "Registrar mi negocio". Su dirección todavía responde "Página no disponible".
2. **Administrador:** entra con `admin@tucarta.local`. En **Negocios pendientes** rechaza sin motivo para ver el error, y después aprueba: se muestra la contraseña temporal del dueño y la dirección del negocio ya abre.
3. **Administrador:** en **Lista y estado de negocios** busca, filtra y suspende un negocio, y abre su dirección para ver el aviso. En **Alta de negocio** crea uno directamente, y en **Moderadores y permisos** crea un moderador y asígnale un negocio.
4. **Dueño:** entra con `juan@tacos-juan.tucarta.local`. Edita el perfil y sube un logo, y mira el cambio en http://tacos-juan.tucarta.localhost. Crea categorías y productos con foto, agrégales opciones con precio extra, busca y filtra en **Productos del menú**, marca uno como agotado, define un horario que cruce la medianoche, activa el cierre temporal y da de alta un empleado.
5. **Separación por negocio:** con la sesión de Juan abre http://sushi-ana.tucarta.localhost/panel. Responde 404, no 403, para no confirmar que existe.
6. **Moderador:** entra con `moderador@tucarta.local`: la lista de negocios sale vacía y la bandeja de pendientes responde 403.

## Qué incluye

| Sprint | Historia | Qué hace |
|---|---|---|
| 4 | US-32 | Base de datos del modelo del Sprint 2, en migraciones y con datos de prueba |
| 4 | US-17 | HTTPS con Traefik y mkcert, ver [docs/servidor.md](docs/servidor.md) |
| 4 | US-31 | Landing page |
| 5 | US-01 | Login unificado, bloqueo de 5 minutos tras 5 intentos y cierre de sesión |
| 5 | US-04 | Catálogo de permisos y separación por negocio: subdominios, filtro automático y Row Level Security en PostgreSQL |
| 5 | US-23 | Empleados con permisos limitados |
| 5 | US-27 | Moderadores con permisos por negocio |
| 5 y 6 | US-33, US-34 | Kit de diseño, ver [docs/kit-diseno.md](docs/kit-diseno.md) |
| 6 | US-02a | Alta de negocio por el administrador, con la cuenta de su dueño |
| 6 | US-02b | La dirección del negocio responde según su estado |
| 6 | US-03 | Lista de negocios: búsqueda, filtro, paginación, suspender y reactivar |
| 6 | US-26a | Auto-registro público |
| 7 | US-26b | Bandeja de pendientes: aprobar o rechazar con motivo |
| 7 | US-05 | Perfil del negocio y logo |
| 7 | US-06 | Categorías del menú |
| 7 | US-19a | Horarios de atención, incluso cruzando la medianoche |
| 7 | US-19b | Cierre temporal y bloqueo de pedidos en el servidor |
| 8 | US-07a | Crear y editar productos |
| 8 | US-07b | Lista de productos: búsqueda, filtros y eliminar con confirmación |
| 8 | US-16 | Fotos optimizadas: WebP de 1200 px, miniatura de 400 px y carpeta por negocio |
| 8 | US-18a, US-18b | Grupos de opciones de un producto, opciones con precio extra y copia a muchos productos a la vez |
| 8 | US-08 | Marcar un producto agotado o disponible sin recargar la página |

Llegan en sprints posteriores: menú público (US-11), carrito y pedidos por WhatsApp (US-13 y US-14). Por eso la página de un negocio es provisional, y el bloqueo de pedidos y algunos permisos de empleado se comprueban en rutas de prueba.

## Direcciones

| Dirección | Quién | Qué es |
|---|---|---|
| `tucarta.localhost/` | Todos | Landing page |
| `tucarta.localhost/registrar-negocio` | Todos | Auto-registro de un negocio |
| `auth.tucarta.localhost/` | Todos | Login unificado |
| `auth.tucarta.localhost/panel` | Administrador y moderadores | Panel central |
| `auth.tucarta.localhost/panel/negocios` | Administrador y moderadores con permiso | Lista y estado de negocios |
| `auth.tucarta.localhost/panel/negocios/crear` | Administrador | Alta de negocio |
| `auth.tucarta.localhost/panel/negocios/pendientes` | Administrador | Bandeja de pendientes |
| `auth.tucarta.localhost/panel/moderadores` | Administrador | Moderadores y permisos |
| `{subdominio}.tucarta.localhost/` | Todos | Página del negocio |
| `{subdominio}.tucarta.localhost/panel` | Dueño y empleados | Panel del negocio |
| `{subdominio}.tucarta.localhost/panel/perfil` | Dueño | Perfil y logo |
| `{subdominio}.tucarta.localhost/panel/categorias` | Dueño | Categorías del menú |
| `{subdominio}.tucarta.localhost/panel/productos` | Dueño | Productos del menú |
| `{subdominio}.tucarta.localhost/panel/horarios` | Dueño | Horarios de atención |
| `{subdominio}.tucarta.localhost/panel/cierre-temporal` | Dueño | Cierre temporal |
| `{subdominio}.tucarta.localhost/panel/equipo` | Dueño | Equipo de trabajo |

Reglas:

- Un negocio activo abre su página. Uno suspendido muestra un aviso, sin borrar su información. Uno pendiente, rechazado o que no existe responde la misma página 404, para no revelar cuál es cuál.
- La dirección web usa solo minúsculas, números y guion, de 3 a 30 caracteres. `auth`, `www`, `admin`, `api`, `app`, `mail` y `traefik` están reservadas. La de un negocio rechazado queda libre.
- Sin sesión, los paneles mandan al login. Quien intenta abrir algo de otro negocio recibe 404, nunca 403. Quien pertenece al negocio pero no tiene el permiso recibe 403.

Los detalles están en [docs/seguridad-por-negocio.md](docs/seguridad-por-negocio.md).

## Correr las pruebas

Con el proyecto levantado (tardan varios minutos en el disco de Windows):

```
docker compose exec laravel.test php artisan test
```

La base de datos se puede abrir con DBeaver en `localhost`, puerto `5432`, base `tucarta`, usuario `sail`, contraseña `password`.

## Stack

- Laravel 13 (Blade, Eloquent) con PHP 8.5 y Laravel Sail (Docker)
- PostgreSQL 16 con Row Level Security
- Tailwind CSS y Vite
- Traefik y mkcert para HTTPS
- Pruebas con PHPUnit y GitHub Actions

## Para el equipo

La instalación con Sail, los permisos y el flujo de Git están en [docs/desarrollo.md](docs/desarrollo.md).

## Equipo

- Camila Rodríguez Blanco (Scrum Master)
- Daniel Esparza Carlos
- Jacobo David Beltrán Reyes
- Max Alejandro Campuzano Ávila
- Sebastián Cruz Vázquez
