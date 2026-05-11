<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Http\JsonResponse;

// Jelszó Tényleges Visszaállítása Kontroller
class ResetPasswordController extends Controller
{
    /**
     * Végrehajtja a jelszó módosítását az adatbázisban a megadott e-mail levélben kapott token alapján.
     */
    public function reset(Request $request): JsonResponse
    {
        // 1. Szigorú validáció
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()], // 'confirmed' -> automatikusan ellenőrzi a password_confirmation mezőt!
        ]);

        // 2. A Laravel beépített Password kliense ellenőrzi a tokent,
        // és ha minden egyezik, ez a névtelen (Closure) függvény fogja a tényleges mentést elvégezni.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // ForceFill kicselezi a $fillable korlátozást (hisz a jelszó elvileg nem lenne így írható).
                $user->forceFill([
                    'password' => Hash::make($password), // A jelszó titkosítása Hash-eléssel (Bcrypt/Argon2)
                    'remember_token' => Str::random(60), // Biztonságból újrageneráljuk a "Emlékezz rám" tokent, kijelentkeztetve minden régit.
                ])->save();
            }
        );

        // 3. API Visszatérés a kliensnek (Vue Fronted) a kimenetel alapján
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 422);
    }
}
