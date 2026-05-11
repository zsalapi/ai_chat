<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Services\ChatService;
use Illuminate\Http\Request;

// A Vendég Csetelő Kontroller (Guest Chat)
class ChatController extends Controller
{
    protected ChatService $chatService;

    // FÜGGŐSÉG BEFECSKENDEZÉS (Dependency Injection):
    // A logikát kiszerveztük a ChatService.php-ba, hogy tesztelhetőbb legyen.
    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;

        // API Rate Limiting (Kérés Limitálás) - védekezés a DDoS támadások ellen
        // 60 kérés percenként IP címenként.
        $this->middleware('throttle:api');
    }

    /**
     * Új beszélgetés indítása (Látogató kattintására)
     */
    public function start(Request $request)
    {
        $chat = $this->chatService->start($request, auth('api')->user());

        return response()->json([
            'success' => true,
            'session_id' => $chat->session_id, // Egyedi azonosító a cookie-ból
            'chat' => $chat,
        ], 200);
    }

    /**
     * Üzenetek betöltése a vendég böngészőjébe
     */
    public function messages($sessionId)
    {
        $data = $this->chatService->getMessages($sessionId);

        return response()->json([
            'success' => true,
            'messages' => $data['messages'],
            'chat' => $data['chat'],
        ], 200);
    }

    /**
     * Ha a Látogató ír valamit a chatbe
     */
    public function send(ChatMessageRequest $request, $sessionId)
    {
        // 1. A validációt most már automatikusan elvégzi a ChatMessageRequest.

        // 2. Mentés a Szolgáltatásos réteggel (a tartalom már tisztított)
        $messages = $this->chatService->sendMessage(
            $sessionId,
            $request->validated('content'),
            auth('api')->user()
        );

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ], 200);
    }

    /**
     * Eszkaláció jelzése: "Kérek egy igazi embert!" gomb a chatben.
     */
    public function escalate($sessionId)
    {
        $chat = $this->chatService->escalate($sessionId);

        return response()->json(['success' => true, 'chat' => $chat], 200);
    }
}
