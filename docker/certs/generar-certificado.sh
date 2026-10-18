#!/usr/bin/env bash
# Certificado para TUCARTA_DOMINIO y sus subdominios (requiere mkcert)
# Uso: ./docker/certs/generar-certificado.sh

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
