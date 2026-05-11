<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\JsonResponse;

// Elfelejtett jelszó (E-mail küldő) Kontroller
class ForgotPasswordController extends Controller
{
    /**
     * Visszaállító link küldése a megadott e-mail címre.
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        // Validáció: Kötelező és formátumra is érvényes e-mail cím
        $request->validate(['email' => 'required|email']);

        // A Laravel Password::sendResetLink() belső logikája keresi meg a usert 
        // a "users" táblában, legenerál egy tokent a "password_reset_tokens" táblába, 
        // majd pedig elküldi a beépített értesítő e-mailt a levélküldő rendszeren keresztül.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Visszatérés a kliensnek (Vue)
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200) // Sikeres elküldés
            : response()->json(['error' => __($status)], 422); // Ilyen e-mail nincs a rendszerben
    }
}
