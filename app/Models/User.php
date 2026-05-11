<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

// A User modell képviseli a felhasználókat az adatbázis "users" táblájában.
class User extends Authenticatable
{
    // A Trait-ek (használatba vett osztályok) extra funkciókat adnak a Userhez.
    // HasApiTokens: Lehetővé teszi az API tokenek generálását a Passporttal.
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tömegesen kitölthető (Mass assignable) attribútumok.
     * Csak ezek a mezők tölthetők fel egyszerre egy tömbből, védelem a "Mass Assignment Vulnerability" ellen.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // A jogosultság, pl. 'agent' vagy 'admin'
    ];

    /**
     * Rejtett mezők, amelyek NEM jelennek meg, ha lekérünk egy User modellt az API-n keresztül.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Típuskonverziók (Casting).
     * Megmondja a Laravelnek, hogy hogyan értelmezzen és alakítson át egy-egy adatbázis mezőt.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Automatikusan hasheli a jelszót mentéskor!
    ];

    /**
     * EGY-A-TÖBBHÖZ KAPCSTOLAT (One-to-Many).
     * Egy User-nek több Event-je is lehet.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
