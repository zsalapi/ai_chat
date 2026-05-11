#!/bin/bash

# Exit on error
set -e

# Check if running as root
if [ "$EUID" -ne 0 ]; then
  echo "Kérlek, futtasd rootként (sudo ./deploy/install.sh)"
  exit
fi

# CONFIGURATION (Edit these as needed or the script will ask)
# ---------------------------------------------------------
DOMAIN=${1:-"ucc-chat.local"} # Domain can be provided as first parameter
DB_NAME="ucc_chat"
DB_USER="ucc_user"
DB_PASS=$(openssl rand -base64 12) # Generated password
PROJECT_PATH=$(pwd) # Assuming running from project root
# ---------------------------------------------------------

echo ">>> Telepítés indítása Debian 13 rendszeren..."
echo ">>> Domain: $DOMAIN"
echo ">>> Projekt könyvtár: $PROJECT_PATH"

# 1. System update and base packages
echo ">>> Csomagok frissítése..."
export DEBIAN_FRONTEND=noninteractive
apt-get update && apt-get upgrade -y -q
apt-get install -y -q curl git unzip gnupg2 ca-certificates lsb-release

# 2. Install Apache, MariaDB, Redis, Supervisor
echo ">>> Webszerver és adatbázis telepítése..."
apt-get install -y -q apache2 mariadb-server redis-server supervisor

# 3. Install PHP (Debian 13 default PHP version, typically 8.2+)
echo ">>> PHP és kiegészítők telepítése..."
apt-get install -y -q php php-cli php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-bcmath php-gd php-redis libapache2-mod-php certbot python3-certbot-apache

# 4. Install Node.js (20.x)
echo ">>> Node.js telepítése..."
mkdir -p /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
apt-get update
apt-get install -y -q nodejs

# 5. Install Composer
if ! command -v composer &> /dev/null; then
    echo ">>> Composer telepítése..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# 6. Create Database
echo ">>> Adatbázis konfigurálása..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;"
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -u root -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

echo ">>> Adatbázis elkészült. Felhasználó: ${DB_USER}, Jelszó: ${DB_PASS}"

# 7. Configure Laravel (.env)
echo ">>> Laravel környezet beállítása..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Update .env with sed command
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=https://${DOMAIN}|" .env

# Security hardening for production
# Ensures debug mode is off and environment is set to production on non-local domains to prevent leak of sensitive info.
if [[ "$DOMAIN" != *.local ]] && [[ "$DOMAIN" != "localhost" ]]; then
    sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
fi

# 8. Install dependencies and Build
echo ">>> Composer és NPM csomagok telepítése..."
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 9. Laravel commands
echo ">>> Kulcs generálás és migráció..."
php artisan key:generate
php artisan storage:link
php artisan migrate --force

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 10. Set permissions
echo ">>> Jogosultságok beállítása (www-data)..."
chown -R www-data:www-data $PROJECT_PATH
find $PROJECT_PATH -type f -exec chmod 664 {} \;
find $PROJECT_PATH -type d -exec chmod 775 {} \;
chmod -R 775 $PROJECT_PATH/storage
chmod -R 775 $PROJECT_PATH/bootstrap/cache

# 11. Apache Configuration
echo ">>> Apache beállítása..."

# Copy config file and replace placeholders
cp deploy/apache.conf /etc/apache2/sites-available/ucc-chat.conf
sed -i "s|{{DOMAIN}}|${DOMAIN}|g" /etc/apache2/sites-available/ucc-chat.conf
sed -i "s|{{PROJECT_PATH}}|${PROJECT_PATH}|g" /etc/apache2/sites-available/ucc-chat.conf

# Enable modules
a2enmod rewrite
a2enmod headers

# Enable site
a2dissite 000-default.conf || true
a2ensite ucc-chat.conf

# 12. SSL Setup
if [[ "$DOMAIN" == *.local ]] || [[ "$DOMAIN" == "localhost" ]]; then
    echo ">>> Helyi domain (${DOMAIN}) észlelve, önaláírt tanúsítvány generálása..."

    # Generate self-signed certificate
    bash deploy/generate-self-signed-cert.sh $DOMAIN

    # Copy Apache SSL config
    cp deploy/apache-ssl.conf /etc/apache2/sites-available/ucc-chat-ssl.conf
    sed -i "s|{{DOMAIN}}|${DOMAIN}|g" /etc/apache2/sites-available/ucc-chat-ssl.conf
    sed -i "s|{{PROJECT_PATH}}|${PROJECT_PATH}|g" /etc/apache2/sites-available/ucc-chat-ssl.conf

    # Enable SSL module and site
    a2enmod ssl
    a2ensite ucc-chat-ssl.conf
else
    echo ">>> Publikus domain (${DOMAIN}) észlelve, SSL beállítása Certbot-tal..."
    # Restart service before Certbot
    systemctl restart apache2
    echo ">>> FONTOS: Ehhez a(z) '$DOMAIN' domainnek a szerver IP címére kell mutatnia, és elérhetőnek kell lennie a 80-as porton."
    # Run Certbot to create SSL vhost and set up redirect
    certbot --apache --non-interactive --agree-tos --redirect --email admin@$DOMAIN -d $DOMAIN
fi

# 13. Restart Apache with final configuration
systemctl restart apache2

# 14. Supervisor Configuration
echo ">>> Supervisor beállítása..."
cp deploy/supervisor-worker.conf /etc/supervisor/conf.d/ucc-worker.conf
sed -i "s|{{PROJECT_PATH}}|${PROJECT_PATH}|g" /etc/supervisor/conf.d/ucc-worker.conf

# Restart services
echo ">>> Szolgáltatások újraindítása..."
supervisorctl reread
supervisorctl update
supervisorctl start all

echo "----------------------------------------------------------------"
echo " TELEPÍTÉS KÉSZ!"
echo "----------------------------------------------------------------"
echo " Elérhető itt: https://${DOMAIN}"
echo " Adatbázis jelszó: ${DB_PASS}"
echo " (A jelszó elmentve a .env fájlba)"
echo "----------------------------------------------------------------"
