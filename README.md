# TuCarta

Plataforma de catálogos digitales para restaurantes y negocios pequeños: cada negocio tiene su menú en su propia dirección web y recibe los pedidos por WhatsApp. TuCarta no cobra ni procesa pagos.

Proyecto de la materia Arquitectura Avanzada de Aplicaciones Web (UTSLP).

## Stack

- Laravel 13 (Blade, Eloquent) con Laravel Sail (Docker)
- PHP 8.5 (el que usa la imagen de Sail del proyecto)
- PostgreSQL 16
- Tailwind CSS, Alpine.js y Vite
- Traefik y mkcert para HTTPS
- Pruebas con Pest/PHPUnit y GitHub Actions

## Requisitos

- Git
- Docker y Docker Compose
- En Windows: WSL2 con Docker Desktop (ejecuta todos los comandos dentro de WSL)

No hace falta instalar PHP, Composer ni PostgreSQL en tu computadora.

## Instalación

1. Clona el repositorio y entra a la carpeta:

   ```bash
   git clone https://github.com/Daniel-Esparza/TuCarta.git
   cd TuCarta
   ```

2. Copia el archivo de configuración:

   ```bash
   cp .env.example .env
   ```

3. Instala las dependencias de PHP usando Docker:

   ```bash
   docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" -w /app composer:latest composer install --ignore-platform-reqs
   ```

4. Levanta los contenedores (la primera vez tarda unos minutos):

   ```bash
   ./vendor/bin/sail up -d
   ```

5. Genera la llave de la aplicación y crea la base de datos con datos de prueba:

   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

   Si sale un error de conexión a `pgsql`, espera unos segundos a que PostgreSQL termine de arrancar y vuelve a correr el comando.

6. Instala y compila los archivos de interfaz:

   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

7. Abre http://localhost en el navegador.

Si el puerto 80 de tu computadora está ocupado, agrega `APP_PORT=8080` en tu `.env` y abre http://localhost:8080.

### Si ya tenías el proyecto instalado

El proyecto cambió de PostgreSQL 18 a PostgreSQL 16 (la versión que define el stack), y el volumen anterior de la base de datos no es compatible. Bórralo **una sola vez** y vuelve a crear la base:

```bash
./vendor/bin/sail down -v
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```

## Acceso

| Qué | Dirección | Notas |
|---|---|---|
| Aplicación (HTTP) | http://localhost | Modo normal, sin certificado |
| Aplicación (HTTPS) | https://tucarta.localhost y https://cualquier-negocio.tucarta.localhost | Requiere activar Traefik y generar el certificado: ver [docs/servidor.md](docs/servidor.md) |
| Base de datos | `localhost`, puerto `5432` | Base, usuario y contraseña: los de `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` de tu `.env`. Se puede abrir con DBeaver |
| Estado de la aplicación | http://localhost/up | Responde 200 si Laravel está arriba |

Todo lo que termina en `.localhost` lo resuelven los navegadores hacia tu propia computadora, así que cualquier subdominio (`pizza-nova.tucarta.localhost`, `lo-que-sea.tucarta.localhost`) llega a la aplicación sin configurar nada.

## Rutas

| Ruta | Qué hace |
|---|---|
| `/` | Landing page: qué es TuCarta, cómo funciona el pedido por WhatsApp y el llamado a registrar un negocio |
| `/up` | Revisión de estado de Laravel |

Por ahora todos los subdominios muestran la misma landing. La carta de cada negocio en su subdominio, el inicio de sesión y los paneles llegan en los siguientes sprints.

## Funcionamiento actual

Hasta el cierre del Sprint 4 el proyecto tiene:

- **Base de datos completa del modelo del Sprint 2**, en migraciones de Laravel:
  - Núcleo: `negocios`, `usuarios`, `permisos`, `usuario_permisos`.
  - Catálogo: `categorias`, `items`, `grupos_opciones`, `opciones`, `horarios`.
  - Pedidos: `pedidos`, `pedido_items`, `pedido_item_opciones`, `pedido_estado_historial`, con los tipos `tipo_entrega` y `estado_pedido`.
- **Datos de prueba** (usuarios y negocios, ver abajo) que se cargan con `migrate --seed`.
- **HTTPS local** con Traefik y mkcert, con redirección automática de HTTP a HTTPS.
- **Landing page** pública.
- **Pruebas automáticas** que corren en cada Pull Request con PostgreSQL 16.

Las cuentas usan un tipo base en `usuarios.rol`: `administrador`, `negocio` o `cliente`. El moderador y el empleado no son tipos aparte: son un administrador o un negocio con menos permisos en `usuario_permisos`.

## Datos de prueba

Se crean con `./vendor/bin/sail artisan migrate:fresh --seed`.

### Usuarios

La contraseña de todas las cuentas es `password`.

| Correo | Tipo | Para qué sirve |
|---|---|---|
| `admin@tucarta.local` | administrador | Administrador de la plataforma |
| `moderador@tucarta.local` | administrador | Moderador sin ningún permiso asignado |
| `juan@tacos-juan.tucarta.local` | negocio | Dueño de Tacos Juan |
| `ana@sushi-ana.tucarta.local` | negocio | Dueña de Sushi Ana |

Las cuentas del personal de un negocio usan el formato `nombre@subdominio.tucarta.local`. El inicio de sesión todavía no está disponible: las cuentas ya existen en la base para cuando llegue.

### Negocios

| Subdominio | Negocio | Dueño | Carta |
|---|---|---|---|
| `tacos-juan` | Tacos Juan | `juan@tacos-juan.tucarta.local` | Sin productos |
| `sushi-ana` | Sushi Ana | `ana@sushi-ana.tucarta.local` | Sin productos |
| `pizza-nova` | Pizza Nova | Sin dueño | 2 categorías, 3 productos y horario de lunes a domingo. La Pizza Margarita tiene el grupo de opciones "Tamaño" (chica, mediana y grande) |
| `dragon-z` | Dragon ball Z | Sin dueño | 3 categorías, 4 productos (uno marcado como agotado) y horario de 6 días |

Todos los negocios están en estado `activo`.

## Comandos útiles

| Qué quieres hacer | Comando |
|---|---|
| Levantar el proyecto | `./vendor/bin/sail up -d` |
| Apagarlo | `./vendor/bin/sail down` |
| Recrear la base con datos de prueba | `./vendor/bin/sail artisan migrate:fresh --seed` |
| Correr las pruebas | `./vendor/bin/sail artisan test` |
| Ver las rutas | `./vendor/bin/sail artisan route:list --except-vendor` |
| Abrir una terminal dentro del contenedor | `./vendor/bin/sail shell` |

Tip: puedes crear un alias `sail` con `alias sail='./vendor/bin/sail'`.

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

Ejemplo:

```bash
git checkout dev
git pull
git checkout -b feature/US-32-migraciones-nucleo
# ...trabajas, haces commits...
git push -u origin feature/US-32-migraciones-nucleo
# Después abres el Pull Request hacia dev en GitHub
```

## Documentación

- [docs/servidor.md](docs/servidor.md): dominio, HTTPS con Traefik y mkcert, y los pasos para el servidor del equipo.

## Equipo

- Camila Rodríguez Blanco (Scrum Master)
- Daniel Esparza Carlos
- Jacobo David Beltrán Reyes
- Max Alejandro Campuzano Ávila
- Sebastián Cruz Vázquez
