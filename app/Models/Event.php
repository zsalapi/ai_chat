<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Esemény (Event) Modell
class Event extends Model
{
    use HasFactory;
    
    // Tömeges hozzárendelés engedélyezése ezekre a mezőkre (Mass Assignment)
    protected $fillable = ['title', 'occurrence', 'description', 'user_id'];

    /**
     * VISSZA-KAPCSOLAT (Belongs To).
     * Minden esemény egy specifikus Felhasználóhoz (User) tartozik, aki létrehozta.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
