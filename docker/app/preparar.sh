#!/usr/bin/env bash
# Primer arranque solo con Docker: .env, dependencias, llave y base de datos.
set -e

cd /var/www/html
usuario="${WWWUSER:-1000}:${WWWGROUP:-1000}"

como_usuario() {
    gosu "$usuario" env HOME=/tmp COMPOSER_PROCESS_TIMEOUT=0 "$@"
}

if [ ! -f .env ]; then
    como_usuario cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    como_usuario composer install --no-interaction --prefer-dist
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    como_usuario php artisan key:generate --force
fi

for intento in $(seq 1 30); do
    if estado=$(como_usuario php artisan migrate:status 2>&1); then
        break
    fi
    if echo "$estado" | grep -q 'Migration table not found'; then
        como_usuario php artisan migrate --seed --force
        break
    fi
    sleep 2
done

exec start-container "$@"
