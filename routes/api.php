<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AgentChatController;
use App\Http\Controllers\Api\EventController;

/*
|--------------------------------------------------------------------------
| API Útvonalak (Routes)
|--------------------------------------------------------------------------
| Ebbe a fájlba jönnek az API hívások (végpontok). 
| Alapértelmezetten a Laravel ezeknek az elejére teszi az "/api" szót.
| A frontend (Vue) "axios" segítségével ezeket hívja meg aszinkron.
*/

// --- NYILVÁNOS VÉGPONTOK (Nem kell hozzájuk bejelentkezés) ---

// Bejelentkezés végpont
Route::post('login', [AuthController::class, 'login']);

// Jelszó visszaállítás végpontjai
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

// VENDÉG (Guest) chat végpontok
Route::post('chat/start', [ChatController::class, 'start']);
Route::get('chat/{session_id}/messages', [ChatController::class, 'messages']);
Route::post('chat/{session_id}/message', [ChatController::class, 'send']);
Route::post('chat/{session_id}/escalate', [ChatController::class, 'escalate']);

// --- VÉDETT VÉGPONTOK (Csak bejelentkezett felhasználók láthatják) ---
// A "middleware('auth:api')" mondja meg a Laravelnek, hogy ellenőrizze a tokent (Passport / Sanctum).
Route::middleware('auth:api')->group(function () {
    
    // API Erőforrás (Resource). Automatikusan legyártja az index, store, show, update, destroy végpontokat.
    Route::apiResource('events', EventController::class);
    
    // Kijelentkezés
    Route::post('logout', [AuthController::class, 'logout']);

    // ÜGYINTÉZŐI (Agent) chat végpontok a Dashboardhoz.
    Route::get('agent/chats', [AgentChatController::class, 'index']); // Beszélgetések listája
    Route::get('agent/chats/{chat}/messages', [AgentChatController::class, 'getMessages']); // Egy chat üzenetei
    Route::post('agent/chats/{chat}/message', [AgentChatController::class, 'sendMessage']); // Üzenet küldése
    Route::post('agent/chats/{chat}/assign', [AgentChatController::class, 'assignChat']);   // Chat átvétele
    Route::get('agent/closed-chats', [AgentChatController::class, 'getClosedChats']);       // Archívum
    Route::post('agent/chats/{chat}/close', [AgentChatController::class, 'closeChat']);     // Chat lezárása

});

// Példa egy egyszerű védett végpontra, ami visszaadja a jelenlegi felhasználó adatait.
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

