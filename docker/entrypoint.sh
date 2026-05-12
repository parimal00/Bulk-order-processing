#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

if ! php -r "require '/var/www/html/vendor/autoload.php';" >/dev/null 2>&1; then
  echo "Composer platform check failed for current PHP. Reinstalling dependencies inside container..."
  composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
  npm install
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  if ! php artisan migrate --force; then
    echo "Migration failed. App will still start; run migrations manually after DB is ready."
  fi
fi

exec "$@"
