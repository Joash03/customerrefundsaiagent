#!/bin/sh
set -e

# A fresh key per container when none is supplied (sessions/tokens don't need to survive rebuilds for a demo).
if [ -z "$APP_KEY" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
fi

echo "Waiting for the database..."
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }' 2>/dev/null; do
    sleep 2
done

php artisan migrate --force
# The seeders skip data that already exists, so restarts keep previous requests.
php artisan db:seed --force
php artisan config:cache
php artisan route:cache

exec "$@"
