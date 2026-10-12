# TuCarta

Plataforma de catálogos digitales para restaurantes y negocios pequeños: cada negocio tiene su menú en su propia dirección web y recibe los pedidos por WhatsApp. TuCarta no cobra ni procesa pagos.

Proyecto de la materia Arquitectura Avanzada de Aplicaciones Web (UTSLP).

## Stack

- Laravel 13 (Blade, Eloquent) con Laravel Sail (Docker)
- PHP 8.5 (el que usa la imagen de Sail del proyecto)
- PostgreSQL 16
- Tailwind CSS, Alpine.js y Vite
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

6. Instala y compila los archivos de interfaz:

   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

7. Abre http://localhost en el navegador.

Si el puerto 80 de tu computadora está ocupado, agrega `APP_PORT=8080` en tu `.env` y abre http://localhost:8080.

## Comandos útiles

| Qué quieres hacer | Comando |
|---|---|
| Levantar el proyecto | `./vendor/bin/sail up -d` |
| Apagarlo | `./vendor/bin/sail down` |
| Recrear la base con datos de prueba | `./vendor/bin/sail artisan migrate:fresh --seed` |
| Correr las pruebas | `./vendor/bin/sail artisan test` |
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

## Servidor del equipo

La configuración del dominio interno y del HTTPS del servidor se documenta en `docs/servidor.md` 

## Equipo

- Camila Rodríguez Blanco (Scrum Master)
- Daniel Esparza Carlos
- Jacobo David Beltrán Reyes
- Max Alejandro Campuzano Ávila
- Sebastián Cruz Vázquez
