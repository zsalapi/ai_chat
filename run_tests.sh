#!/bin/bash

echo "🔄 Beragadt teszt folyamatok és lockok keresése és kilövése..."
# Csak azokat a PHP folyamatokat lőjük ki, amik biztosan a teszteléshez tartoznak, 
# hogy az esetleges artisan serve vagy npm run dev megmaradjon.
pkill -9 -f "php artisan test" || true
pkill -9 -f "phpunit" || true

# Egy kis szünet, hogy a bezárt kapcsolatok miatt a PostgreSQL / SQLite 
# biztonságosan visszafordítsa (rollback) az elhagyott tranzakciókat és kioldja a lockokat
sleep 2

echo "🧹 Cache és konfiguráció takarítása..."
php artisan optimize:clear

echo "🚀 Tesztek futtatása biztonságosan elszigetelve..."
# Felülírjuk ideiglenesen a legfontosabb környezeti változókat futási időben,
# hogy garantáljuk, hogy mondjuk a Websockets vagy Reverb nem áll be várakozni.
DB_DATABASE=uuc_chat_testing BROADCAST_DRIVER=log CACHE_DRIVER=array QUEUE_CONNECTION=sync php artisan test
