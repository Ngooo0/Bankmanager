# ...existing code...
#!/bin/sh

echo "🚀 Starting BankManager application..."

# Vérifier que PHP fonctionne
if ! php --version > /dev/null 2>&1; then
    echo "❌ PHP is not available"
    exit 1
fi

echo "✅ PHP is working"

# Vérifier que Laravel est installé
if [ ! -f "artisan" ]; then
    echo "❌ Laravel artisan file not found"
    exit 1
fi

echo "✅ Laravel artisan found"

# If DATABASE_URL is provided, parse and export DB_* vars for Laravel
if [ -n "$DATABASE_URL" ]; then
  echo "🔗 Parsing DATABASE_URL..."
  eval $(php -r '
    $url = getenv("DATABASE_URL");
    $p = parse_url($url);
    if ($p) {
      $scheme = isset($p["scheme"]) ? $p["scheme"] : "pgsql";
      $host = isset($p["host"]) ? $p["host"] : "";
      $port = isset($p["port"]) ? $p["port"] : "";
      $user = isset($p["user"]) ? $p["user"] : "";
      $pass = isset($p["pass"]) ? $p["pass"] : "";
      $path = isset($p["path"]) ? ltrim($p["path"],"/") : "";
      parse_str(isset($p["query"]) ? $p["query"] : "", $q);
      echo "export DB_CONNECTION=$scheme ";
      echo "export DB_HOST=$host ";
      if ($port !== "") echo "export DB_PORT=$port ";
      echo "export DB_DATABASE=$path ";
      if ($user !== "") echo "export DB_USERNAME=$user ";
      if ($pass !== "") echo "export DB_PASSWORD=$pass ";
      if (isset($q["sslmode"])) echo "export DB_SSLMODE=".$q["sslmode"]." ";
    }
  ')
fi

# Nettoyer les caches avant de les régénérer
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Régénérer les caches (tolerant)
echo "🔄 Regenerating caches..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Fonction utilitaire : test de connectivité TCP via PHP
check_db() {
  php -r '
    $host=getenv("DB_HOST");
    $port=getenv("DB_PORT")?:5432;
    if (!$host) { echo "0"; exit; }
    $s=@fsockopen($host,$port,$errno,$errstr,2);
    if ($s) { fclose($s); echo "1"; } else { echo "0"; }
  '
}

# Attendre la DB (retry)
echo "🔍 Testing database connectivity..."
retries=0
max_retries=6
wait_seconds=3
db_ok=0

while [ $retries -lt $max_retries ]; do
  if [ -z "$DB_HOST" ]; then
    echo "⚠️ DB_HOST not set — skipping DB connectivity test"
    break
  fi

  reachable=$(check_db)
  if [ "$reachable" = "1" ]; then
    echo "✅ Database host $DB_HOST:$DB_PORT reachable"
    db_ok=1
    break
  else
    echo "⏳ Database not reachable yet ($DB_HOST:$DB_PORT) — retry $((retries+1))/$max_retries"
    retries=$((retries+1))
    sleep $wait_seconds
  fi
done

if [ "$db_ok" = "1" ]; then
    # Exécuter les migrations si nécessaire
    echo "🗄️ Running migrations..."
    if php artisan migrate --force; then
        echo "✅ Migrations completed"
    else
        echo "⚠️ Migrations failed, continuing..."
    fi
else
    echo "⚠️ Database not accessible, skipping migrations"
fi

echo "🎯 Starting Laravel application..."

# Lancer la commande passée en argument (ex: php artisan serve ou via Apache/Nginx)
exec "$@"
# ...existing code...#!/bin/sh

echo "🚀 Starting BankManager application..."

# Vérifier que PHP fonctionne
if ! php --version > /dev/null 2>&1; then
    echo "❌ PHP is not available"
    exit 1
fi

echo "✅ PHP is working"

# Vérifier que Laravel est installé
if [ ! -f "artisan" ]; then
    echo "❌ Laravel artisan file not found"
    exit 1
fi

echo "✅ Laravel artisan found"

# Nettoyer les caches avant de les régénérer
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Régénérer les caches
echo "🔄 Regenerating caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Tester la connectivité de base de données (optionnel)
echo "🔍 Testing database connectivity..."
if php artisan migrate:status > /dev/null 2>&1; then
    echo "✅ Database is accessible"

    # Exécuter les migrations si nécessaire (ignorer les erreurs de tables existantes)
    echo "🗄️ Running migrations..."
    php artisan migrate --force > /dev/null 2>&1 || echo "⚠️ Some migrations failed (tables may already exist), continuing..."
    echo "✅ Migrations check completed"
else
    echo "⚠️ Database not accessible, skipping migrations"
fi

echo "🎯 Starting Laravel application..."

# Lancer la commande passée en argument
exec "$@"