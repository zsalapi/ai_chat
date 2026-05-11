<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// Autentikációs (Bejelentkezés / Regisztráció) Kontroller
class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    /**
     * Felhasználó bejelentkeztetése és Token generálása.
     */
    public function login(AuthLoginRequest $request) : JsonResponse
    {
        // 1. Az authService->login() meghívására a Service réteg elvégzi a logika nehezét.
        $user = $this->authService->login($request->validated());

        // 2. Ha érvénytelen az email/jelszó, visszatér 401 Unauthorized-el.
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'data' => []
            ], 401);
        }

        // 3. Ha sikeres, csinálunk a Passport segítségével egy tokent, és visszaküldjük a Vue-nak.
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged in',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('laravel-api-token')->accessToken
            ]
        ], 200);
    }

    /**
     * Kijelentkezés (Minden korábbi aktív token visszavonása).
     */
    public function logout(Request $request) : JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'data' => []
        ], 200);
    }

    /**
     * Új felhasználó regisztrálása.
     */
    public function register(AuthRegisterRequest $request) : JsonResponse
    {
        $user = $this->authService->register($request->validated());

        // Egyből gyártunk is neki egy tokent, hogy ne kelljen külön bejelentkeznie a regisztráció után.
        $token = $user->createToken('laravel-api-token')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Account Created Successfully!',
            'data' => ['user' => $user,'token' => $token]
        ], 201); // 201: Created
    }
}
