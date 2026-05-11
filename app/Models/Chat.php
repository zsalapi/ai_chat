<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// A Chat modell az adatbázis "chats" táblájához kapcsolódik.
class Chat extends Model
{
    use HasFactory;

    // Azok a mezők, amiket biztonságosan kitölthetünk közvetlenül az elküldött űrlapok adataiból.
    protected $fillable = [
        'agent_id',      // A kezelő azonosítója, aki felvette a hívást
        'session_id',    // A látogató egyedi azonosítója
        'status',        // Nyitott, folyamatban, vagy lezárt
        'ip_address',    // Látogató IP címe (amit PostgreSQL "inet"-ként optimalizáltunk)
        'last_bot_message_at', // Mikor kapott utoljára automata üzenetet
    ];

    /**
     * JÓVÁHAGYÁS / VISSZA-KAPCSOLAT (Belongs To).
     * Egy Chat EGYETLEN egy Ügyintézőhöz (User) tartozhat.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * EGY-A-TÖBBHÖZ KAPCSTOLAT (Has Many).
     * Egy Chat-hez TÖBB Üzenet (Message) is tartozhat.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_id');
    }
}
