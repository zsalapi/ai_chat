<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Az egyéni üzeneteket reprezentáló Modell
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',   // Melyik beszélgetéshez tartozik
        'sender_id', // Ki küldte (Ha null, akkor a vendég Látogató)
        'content',   // Az üzenet szövege
        'type',      // Szöveg? Rendszerüzenet?
    ];

    /**
     * VISSZA-KAPCSOLAT (Belongs To) a táblán keresztül a Chats felé.
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * VISSZA-KAPCSOLAT a Felhasználókhoz. Ez az "ügynök/vásárló", aki gépelte.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
