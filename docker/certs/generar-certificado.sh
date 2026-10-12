#!/usr/bin/env bash
# Genera (o vuelve a generar) el certificado de TuCarta para el dominio
# configurado en TUCARTA_DOMINIO y todos sus subdominios.
#
# Uso, desde la raíz del proyecto:
#   ./docker/certs/generar-certificado.sh
#
# Requiere mkcert. En Windows con WSL se usa mkcert.exe de Windows, para que
# el certificado quede como confiable en los navegadores de Windows.

set -euo pipefail

cd "$(dirname "$0")/../.."

dominio="${TUCARTA_DOMINIO:-}"
if [ -z "$dominio" ] && [ -f .env ]; then
    dominio="$(grep -E '^TUCARTA_DOMINIO=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"' | tr -d "'")"
fi
dominio="${dominio:-tucarta.localhost}"

if command -v mkcert.exe > /dev/null 2>&1; then
    mkcert=mkcert.exe
elif command -v mkcert > /dev/null 2>&1; then
    mkcert=mkcert
else
    echo "No se encontró mkcert. Revisa docs/servidor.md, sección \"Instalar mkcert\"." >&2
    exit 1
fi

"$mkcert" -install
"$mkcert" \
    -cert-file docker/certs/tucarta.pem \
    -key-file docker/certs/tucarta-key.pem \
    "$dominio" "*.$dominio"

echo
echo "Certificado listo para $dominio y *.$dominio"
echo "Si Traefik ya estaba corriendo, reinícialo: ./vendor/bin/sail restart traefik"
