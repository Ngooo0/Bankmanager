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

# Clear and cache config so generation uses the correct APP_URL
php artisan config:clear
php artisan config:cache || true

# Ensure storage directories exist and are writable
mkdir -p storage/api-docs
chmod -R 775 storage bootstrap/cache || true

# Generate Swagger docs
echo "📚 Generating Swagger docs"
php artisan l5-swagger:generate --no-interaction || echo "Swagger generation failed, continuing..."

# Optional: cache routes and views
php artisan view:cache || true
php artisan route:cache || true

echo "✅ Render build script finished"
