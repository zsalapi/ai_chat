#!/bin/bash

# Exit on error
set -e

echo "========================================================"
echo "   UCC Chat - Hosting Csomagoló (Futtasd a gépeden!)"
echo "========================================================"
echo "Ez a script előkészíti a fájlokat a feltöltéshez."
echo "Mindent egy 'ucc-chat-release' mappába és ZIP-be rak."
echo ""

# 1. Install dependencies (Production mode)
echo ">>> 1. PHP függőségek telepítése (no-dev)..."
composer install --optimize-autoloader --no-dev

echo ">>> 2. Frontend buildelése..."
npm install
npm run build

# 2. Clear cache (IMPORTANT: Do not upload cached paths!)
echo ">>> 3. Cache törlése..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Create target directory
RELEASE_DIR="ucc-chat-release"
rm -rf $RELEASE_DIR
mkdir $RELEASE_DIR

echo ">>> 4. Fájlok másolása a '$RELEASE_DIR' mappába..."
# Copy only necessary files (exclude .git, node_modules, tests)
rsync -av --progress . $RELEASE_DIR \
    --exclude '.git' \
    --exclude '.env' \
    --exclude 'node_modules' \
    --exclude 'tests' \
    --exclude 'storage/logs/*.log' \
    --exclude 'deploy' \
    --exclude 'scripts' \
    --exclude "$RELEASE_DIR"

# 5. Create .htaccess in root
echo ">>> 5. .htaccess generálása..."
cat > "$RELEASE_DIR/.htaccess" <<EOF
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Redirect to public folder
    RewriteRule ^(.*)$ public/\$1 [L]
</IfModule>
EOF

# 6. Create symlink helper
echo ">>> 6. Symlink létrehozó script generálása (public/setup_symlink.php)..."
cat > "$RELEASE_DIR/public/setup_symlink.php" <<'EOF'
<?php
echo "Symlink létrehozása...<br>";
$target = __DIR__ . '/../storage/app/public';
$link = __DIR__ . '/storage';

if (file_exists($link)) {
    echo "A link már létezik.";
} else {
    if (symlink($target, $link)) {
        echo "SIKER! A 'storage' link létrejött.";
    } else {
        echo "HIBA! Nem sikerült létrehozni a linket. Lehet, hogy nincs jogosultság, vagy a 'symlink' funkció tiltva van.";
    }
}
EOF

# 7. Copy .env.example as .env (empty)
cp .env.example "$RELEASE_DIR/.env"

echo ">>> 7. Csomagolás ZIP fájlba..."
zip -r ucc-chat-release.zip $RELEASE_DIR -q

echo ""
echo "========================================================"
echo "   CSOMAGOLÁS KÉSZ!"
echo "========================================================"
echo "A feltöltés menete:"
echo "1. Töltsd fel a '$RELEASE_DIR' mappa tartalmát az FTP-n a 'ucc-chat' könyvtárba."
echo "2. Nevezd át a feltöltött '.env' fájlt, és állítsd be az adatbázis adatait."
echo "   (Ügyelj rá, hogy 'QUEUE_CONNECTION=database' legyen beállítva!)"
echo "3. Importáld az adatbázisodat (pl. phpMyAdmin-ban)."
echo "   (Exportáld ki a helyi gépedről, vagy használd a migrációs fájlokat)."
echo "4. A böngészőben nyisd meg: https://te-domain.hu/ucc-chat/setup_symlink.php"
echo "   (Ez létrehozza a kapcsolatot a képekhez)."
echo "5. Töröld a 'setup_symlink.php' fájlt a szerverről."
echo "========================================================"
