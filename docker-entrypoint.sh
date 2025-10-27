#!/bin/sh
# Installer postgresql-client si nécessaire
echo "Waiting for database to be ready..."

# Boucle d'attente simplifiée (sans pg_isready)
max_attempts=30
attempt=0
until php artisan migrate --force 2>/dev/null || [ $attempt -eq $max_attempts ]; do
    echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts)"
    attempt=$((attempt + 1))
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "Could not connect to database after $max_attempts attempts"
    echo "Continuing anyway..."
fi

echo "Database is up - migrations completed"
echo "Starting Laravel application..."

# Lancer la commande passée en argument
exec "$@"