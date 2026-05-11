<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Itt add meg a frontend alkalmazásod URL-jét.
    // Fejlesztés során ez általában valami, mint 'http://localhost:5173' (Vite) vagy 'http://localhost:3000'.
    // Éles környezetben a .env fájlban add meg a valós domain nevet.
    'allowed_origins' => explode(',', env('FRONTEND_URL', 'http://localhost:5173,https://localhost:5173,https://localhost:8000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Passport (token alapú) authentikációnál false-nak kell lennie.
    'supports_credentials' => false,

];
