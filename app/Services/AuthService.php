<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Autentikációs Üzleti Logika 
class AuthService
{
    protected UserRepositoryInterface $userRepository;

    // FÜGGŐSÉG BEFECSKENDEZÉS: Itt ráadásul REPOSITORY PATTERN-t használunk.
    // Nem közvetlenül a Model-lel beszélgetünk, hanem egy interfésszel, így később cserélhető az adatbázis technológia.
    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function register(array $data)
    {
        // Átadja a Repository-nak a regisztrációt.
        return $this->userRepository->create($data);
    }

    public function login(array $credentials)
    {
        // Megkeresi a usert email alapján
        $user = $this->userRepository->findUserEmail($credentials['email']);

        // Ha létezik a user ÉS a Hash::check (titkosított jelszóellenőrző) is megerősíti a jelszót:
        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $user;
        }

        return null;
    }

    public function logout(User $user)
    {
        // Kijelentkeztetés a Repository-ban (Törli a tokeneket)
        $this->userRepository->logout($user);
    }
}