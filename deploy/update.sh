#!/bin/bash

# Exit on error
set -e

# Check if running as root
if [ "$EUID" -ne 0 ]; then
  echo "Kérlek, futtasd rootként (sudo ./deploy/update.sh)"
  exit
fi

PROJECT_PATH=$(pwd)

echo ">>> Frissítés indítása..."
echo ">>> Projekt könyvtár: $PROJECT_PATH"

# 1. Turn on maintenance mode
echo ">>> Karbantartó mód bekapcsolása..."
php artisan down

# 2. Update code from Git repo
echo ">>> Kód frissítése (git pull)..."
git pull

# 3. Update dependencies
echo ">>> Composer és NPM csomagok telepítése..."
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 4. Migrate database and update caches
echo ">>> Migráció és cache frissítés..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers (to use new code)
echo ">>> Queue workerek újraindítása..."
php artisan queue:restart

# Fix permissions (since build was run as root)
echo ">>> Jogosultságok javítása..."
chown -R www-data:www-data $PROJECT_PATH
chmod -R 775 $PROJECT_PATH/storage
chmod -R 775 $PROJECT_PATH/bootstrap/cache

# 5. Turn off maintenance mode
echo ">>> Karbantartó mód kikapcsolása..."
php artisan up

echo ">>> FRISSÍTÉS KÉSZ!"
