#!/usr/bin/env bash
set -euo pipefail

# Script to be used as Render "Build Command" or executed during deploy.
# It installs deps, ensures env, caches config and generates Swagger docs.

echo "🏗️  Render build script started"

# Install composer dependencies (Render usually runs this itself; safe to run)
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Ensure APP_KEY exists (generate if missing)
if [ -z "${APP_KEY:-}" ] || [[ "${APP_KEY:-}" == base64:* && "${APP_KEY:-}" == "base64:" ]]; then
  echo "🔐 Generating APP_KEY"
  php artisan key:generate --force --no-interaction
fi

# Clear all caches first
echo "🧹 Clearing all caches"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ensure storage directories exist and are writable
mkdir -p storage/api-docs
chmod -R 775 storage bootstrap/cache || true

# Cache config with correct APP_URL
echo "⚙️ Caching configuration"
php artisan config:cache || true

# Force route cache regeneration
echo "🛣️ Caching routes"
php artisan route:cache || echo "Route caching failed, continuing..."

# Cache views
echo "👁️ Caching views"
php artisan view:cache || true

# Generate Swagger docs
echo "📚 Generating Swagger docs"
php artisan l5-swagger:generate --no-interaction || echo "Swagger generation failed, continuing..."

echo "✅ Render build script finished"
