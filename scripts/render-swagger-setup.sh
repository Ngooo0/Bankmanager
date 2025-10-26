#!/usr/bin/env bash
set -euo pipefail

# Script d'aide pour Render : publier les assets/views de L5-Swagger et générer la doc
# Usage (dans Build Command sur Render) :
#   bash ./scripts/render-swagger-setup.sh

echo "[swagger-setup] Publish vendor assets and views (L5-Swagger)"
# publish the package assets/views (force to overwrite if already present)
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

echo "[swagger-setup] Generating Swagger documentation (storage/api-docs/*.json)"
php artisan l5-swagger:generate --no-interaction --force

# Ensure storage is writable by the webserver (Render uses default linux user; adjust if needed)
if command -v chown >/dev/null 2>&1; then
  echo "[swagger-setup] Adjusting storage permissions..."
  chown -R www-data:www-data storage || true
fi
chmod -R ug+rwX storage bootstrap/cache || true

echo "[swagger-setup] Done. Swagger UI assets published and docs generated."
