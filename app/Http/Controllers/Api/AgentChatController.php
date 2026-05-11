<?php

// EZ a Kontroller kifejezetten az Ügyintézői (Agent) funkciókat kezeli a chat-ben (Mások nem láthatják).
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Http\Requests\ChatMessageRequest;
use Illuminate\Http\Request;
use App\Services\AgentChatService;

class AgentChatController extends Controller
{
    protected AgentChatService $agentChatService;

    public function __construct(AgentChatService $agentChatService)
    {
        $this->agentChatService = $agentChatService;

        // API Rate Limit (60 kérés / perc)
        $this->middleware('throttle:api');

        // BELSŐ KÖZTES RÉTEG (Middleware)
        // Megakadályozzuk, hogy MÁS szerepkörrel rendelkezők beférkőzzenek ide.
        $this->middleware(function ($request, $next) {
            // Ha valakinek a 'role' adattagja az adatbázisban NEM 'agent', akkor elutasítjuk egy 403-as kóddal.
            if (!auth('api')->user() || auth('api')->user()->role !== 'agent') {
                abort(403, 'This action is only available for agents.');
            }

            // Ha átment a szűrőn, eleresztjük tovább ($next).
            return $next($request);
        });
    }

    /**
     * Dashboard nézet: Betölti azokat a chateket, amik üresek (nincs még gazdájuk)
     * vagy azokat amik már az adott ügyintézőhöz (auth()->user()) tartoznak.
     */
    public function index(Request $request)
    {
        // Visszaadjuk a szolgáltatott adatot, auth('api')->user() = a jelenleg bejelentkezett ember.
        $chats = $this->agentChatService->getDashboardChats(auth('api')->user(), $request->get('page', 1));

        return response()->json($chats, 200);
    }

    /**
     * Üzenetek betöltése egyetlen adott chathez
     */
    public function getMessages(Chat $chat)
    {
        // Mivel a $chat útvonal-paraméterben (Route Model Binding) érkezik az ID-t megadva (pl. api/agent/chats/5/messages)
        // A Laravel automatikusan csinál ebből nekünk egy igazi Chat Model objektumot! Nem kell $chat = Chat::find($id); -t írnunk!
        $messages = $this->agentChatService->getChatMessages($chat);
        return response()->json($messages, 200);
    }

    /**
     * Üzenet küldése ügyintézőként (Ezzel automatikusan magához is "veszi" a chat-et, ha addig senkié se volt).
     */
    public function sendMessage(Chat $chat, ChatMessageRequest $request)
    {
        // 1. A validációt most már automatikusan elvégzi a ChatMessageRequest.

        // 2. Elküldjük: Melyik chatbe / Ki küldi (Request-ből) / Mit (Validált és tisztított tartalom)
        $message = $this->agentChatService->sendMessage(
            $chat,
            auth('api')->user(),
            $request->validated('content')
        );

        return response()->json($message, 200);
    }

    /**
     * Csak szimplán hozzárendeljük a Chatet a jelenlegi emberhez (anélkül, hogy írnánk bármit).
     */
    public function assignChat(Chat $chat, Request $request)
    {
        $this->agentChatService->assignChat($chat, auth('api')->user());
        return response()->json(['message' => 'Chat successfully assigned to you.'], 200);
    }

    /**
     * Chat manuális lezárása (Pipa gomb rányomásával).
     */
    public function closeChat(Chat $chat, Request $request)
    {
        $this->agentChatService->closeChat($chat, auth('api')->user());
        return response()->json(['message' => 'Chat closed.'], 200);
    }

    /**
     * Lapozható lekérdezése a régi, már lezárt (Archivált) korábbi beszélgetéseknek.
     */
    public function getClosedChats(Request $request)
    {
        $closedChats = $this->agentChatService->getClosedChats();

        return response()->json($closedChats, 200);
    }
}
