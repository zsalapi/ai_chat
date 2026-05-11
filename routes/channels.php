<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Közvetítési Csatornák (Broadcast Channels)
|--------------------------------------------------------------------------
|
| Itt regisztráljuk a rendszerben használt összes valós idejű esemény (WebSocket)
| csatornát. A "channel" az a szoba, amire a frontend/Vue feliratkozik.
| Az itteni függvények (callback-ek) arra valók, hogy ENGEDÉLYEZZÜK:
| belehallgathat-e az adott felhasználó az adott csatornába (Authorization).
|
*/

// Egy privát csatorna a specifikus felhasználónak. Csak a saját azonosítójú szobájába léphet be.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
