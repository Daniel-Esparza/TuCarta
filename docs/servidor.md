# Servidor, dominio y HTTPS

Cómo se sirve TuCarta por HTTPS con un subdominio por negocio (US-17).

## Cómo funciona

```
navegador ──HTTPS──> Traefik (puertos 80 y 443) ──HTTP──> Laravel (contenedor laravel.test)
```

- **Traefik** recibe todo el tráfico. Lo que llega por HTTP lo redirige a HTTPS, y lo que llega por HTTPS lo manda a Laravel.
- **mkcert** crea una autoridad certificadora local y un certificado para el dominio y todos sus subdominios (`*.dominio`). Como esa autoridad se instala en la computadora, el navegador muestra el candado sin advertencias.
- Traefik manda **cualquier** subdominio a Laravel, así que un negocio nuevo funciona sin tocar nada.
- El dominio se controla con **una sola variable**, `TUCARTA_DOMINIO`:

| Dónde | `TUCARTA_DOMINIO` | Quién resuelve los subdominios |
|---|---|---|
| Escritorio local de cada integrante | `tucarta.localhost` | El propio navegador: todo lo que termina en `.localhost` apunta a la misma computadora, sin configurar nada |
| Servidor del equipo (cuando exista) | `tucarta.local` | dnsmasq en el servidor |

Traefik está en el perfil `https` de `compose.yaml`: si no lo activas, el proyecto corre como siempre por HTTP en `http://localhost`, sin certificado. Así lo puede revisar cualquier persona que solo clone el repositorio.

## Archivos

| Archivo | Para qué sirve |
|---|---|
| `compose.yaml` (servicio `traefik`) | Levanta Traefik en los puertos 80 y 443, solo con el perfil `https` |
| `docker/traefik/traefik.yml` | Puertos de entrada y redirección de HTTP a HTTPS |
| `docker/traefik/dinamico.yml` | Manda todo a Laravel y le dice a Traefik qué certificado usar |
| `docker/certs/generar-certificado.sh` | Genera o vuelve a generar el certificado con un solo comando |
| `docker/certs/*.pem` | El certificado generado. No se sube a GitHub: cada quien genera el suyo |
| `docker/dnsmasq/tucarta.conf` | Configuración de dnsmasq para el servidor del equipo |
| `config/tucarta.php` | Lee `TUCARTA_DOMINIO` para que Laravel conozca el dominio base |

## Escritorio local (Windows con WSL2)

### 1. Instalar mkcert (una sola vez)

En **PowerShell de Windows** (no en WSL):

```powershell
winget install FiloSottile.mkcert
```

Compruébalo dentro de WSL:

```bash
mkcert.exe -help
```

Si dice que no lo encuentra, es porque WSL todavía no ve los programas recién instalados en Windows. Reinicia WSL desde PowerShell con `wsl --shutdown` y vuelve a abrir Ubuntu, o crea un acceso directo:

```bash
mkdir -p ~/.local/bin
ln -sf "$(wslpath "$(cmd.exe /c 'echo %LOCALAPPDATA%' 2>/dev/null | tr -d '\r')")"/Microsoft/WinGet/Packages/FiloSottile.mkcert_*/mkcert.exe ~/.local/bin/mkcert.exe
source ~/.profile
```

Se usa la versión de Windows a propósito: así la autoridad certificadora queda instalada en Windows y Chrome y Edge confían en el certificado.

### 2. Configurar el `.env`

```dotenv
APP_URL=https://tucarta.localhost
APP_PORT=8080
TUCARTA_DOMINIO=tucarta.localhost
COMPOSE_PROFILES=https
```

`APP_PORT=8080` es necesario porque Traefik ocupa el puerto 80.

### 3. Generar el certificado

Desde la raíz del proyecto, en WSL:

```bash
./docker/certs/generar-certificado.sh
```

La primera vez Windows pregunta si quieres instalar la autoridad certificadora: acepta.

### 4. Levantar el proyecto

```bash
./vendor/bin/sail up -d
```

Abre https://prueba1.tucarta.localhost: debe salir con candado.

### 5. Firefox

Firefox no usa el almacén de certificados de Windows. Si lo usas, entra a `about:config` y pon `security.enterprise_roots.enabled` en `true`.

## Servidor del equipo

> Pendiente: todavía no hay servidor. Estos pasos quedan listos para cuando se tenga una computadora dedicada (Ubuntu) en la red del equipo.

### 1. Docker y el proyecto

Instala Docker en el servidor, clona el repositorio y sigue el `README`. En el `.env`:

```dotenv
APP_URL=https://tucarta.local
APP_PORT=8080
TUCARTA_DOMINIO=tucarta.local
COMPOSE_PROFILES=https
```

### 2. DNS interno con dnsmasq

```bash
sudo apt install dnsmasq
```

Ubuntu ya trae un DNS local en el puerto 53. Desactívalo para que dnsmasq pueda usar ese puerto:

```bash
sudo sed -i 's/^#\?DNSStubListener=.*/DNSStubListener=no/' /etc/systemd/resolved.conf
sudo systemctl restart systemd-resolved
```

Copia la configuración del proyecto y cambia la IP por la del servidor:

```bash
sudo cp docker/dnsmasq/tucarta.conf /etc/dnsmasq.d/tucarta.conf
sudo nano /etc/dnsmasq.d/tucarta.conf
sudo systemctl restart dnsmasq
```

En cada computadora del equipo (o en el router, para que aplique a toda la red) pon la IP del servidor como DNS principal. Compruébalo desde otra computadora:

```bash
nslookup cualquier-cosa.tucarta.local
```

Debe responder con la IP del servidor.

### 3. Certificado

En el servidor instala mkcert (`sudo apt install mkcert libnss3-tools`) y genera el certificado:

```bash
./docker/certs/generar-certificado.sh
```

Para que las demás computadoras confíen en él, cada integrante instala **una sola vez** la autoridad certificadora del servidor. Su ubicación sale con:

```bash
mkcert -CAROOT
```

Copia el archivo `rootCA.pem` de esa carpeta a cada computadora. En Windows: doble clic, **Instalar certificado**, **Equipo local**, almacén **Entidades de certificación raíz de confianza**. **Nunca compartas `rootCA-key.pem`.**

### 4. Firewall

Abre en el servidor los puertos 53 (DNS, TCP y UDP), 80 y 443.

## Verificación (criterios de US-17)

| Criterio | Cómo se prueba | Escritorio local | Servidor |
|---|---|---|---|
| Los subdominios abren con candado y sin advertencias | Abrir `https://prueba1.<dominio>` y `https://prueba2.<dominio>` | Sí | Desde otra computadora de la red |
| HTTP redirige solo a HTTPS | Abrir `http://prueba1.<dominio>`: la barra debe cambiar a `https://` | Sí | Sí |
| Un subdominio nuevo funciona sin tocar la configuración | Abrir `https://cualquier-nombre.<dominio>` | Sí | Sí |
| El certificado se vuelve a generar con un solo comando | `./docker/certs/generar-certificado.sh` | Sí | Sí |

Desde la terminal también se puede revisar la redirección:

```bash
curl -I http://prueba1.tucarta.localhost
```

Debe responder `301` con `Location: https://prueba1.tucarta.localhost/`.

Si pruebas con el `curl.exe` de Windows en lugar del de WSL, agrega `--ssl-no-revoke`: los certificados de mkcert no tienen lista de revocación y ese `curl` la exige (los navegadores no).

## Problemas comunes

**El puerto 80 o 443 está ocupado.** Otro programa (por ejemplo IIS, Skype o un proyecto de Sail anterior) los está usando. Apágalo, o revisa que `APP_PORT` no sea 80.

**El navegador dice que el certificado no es de confianza.** No se instaló la autoridad certificadora. Vuelve a correr `./docker/certs/generar-certificado.sh` y acepta el aviso de Windows. En Firefox revisa el paso 5.

**Cambié el certificado y no se nota.** Reinicia Traefik: `./vendor/bin/sail restart traefik`.

**Los estilos no cargan con `npm run dev` al entrar por HTTPS.** El servidor de Vite corre por HTTP y el navegador lo bloquea dentro de una página HTTPS. Mientras pruebas por HTTPS, compila los archivos con `./vendor/bin/sail npm run build`.

**Quiero volver a solo HTTP.** Deja `COMPOSE_PROFILES=` vacío, regresa `APP_PORT` a 80 y corre `./vendor/bin/sail down && ./vendor/bin/sail up -d`.
