<?php

namespace App\Services;

use App\Events\ChatStatusUpdated;
use App\Events\NewChatMessage;
use App\Models\Chat;
use App\Models\User;
use App\Repositories\ChatRepositoryInterface;
use App\Repositories\FaqRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatService
{
    // A függőségeket (Repository-kat) interfészeken és a Service-en keresztül tároljuk
    protected ChatRepositoryInterface $chatRepository;
    protected FaqRepositoryInterface $faqRepository;
    protected OllamaService $ollamaService;

    public function __construct(
        ChatRepositoryInterface $chatRepository,
        FaqRepositoryInterface $faqRepository,
        OllamaService $ollamaService
    ) {
        $this->chatRepository = $chatRepository;
        $this->faqRepository = $faqRepository;
        $this->ollamaService = $ollamaService;
    }

    /**
     * Új chat munkamenet indítása a Guest számára.
     * Generál egy egyedi session_id-t, és elmenti a kezdeti üdvözlő üzenetet.
     */

    public function start(Request $request, ?User $user): Chat
    {
        $sessionId = (string) Str::uuid();

        $chat = $this->chatRepository->create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'status' => 'open',
            'user_id' => $user?->id,
        ]);

        $this->chatRepository->addMessage($chat, [
            'content' => 'Hi! I am an automated bot. I will try to answer your questions, but if you like, you can request "Human help" too!',
            'type' => 'bot',
        ]);

        return $chat->load(['agent']);
    }

    /**
     * Lekéri a korábbi beszélgetés előzményeit a session azonosító alapján.
     */
    public function getMessages(string $sessionId): array
    {
        $chat = $this->chatRepository->findBySessionId($sessionId);
        $messages = $chat->messages()->with('sender')->orderBy('created_at')->get();

        return [
            'messages' => $messages,
            'chat' => $chat,
        ];
    }

    /**
     * Lényegi kommunikáció: Itt dolgozzuk fel a vendég üzenetét és
     * RAG (Retrieval-Augmented Generation) használatával generálunk választ.
     */
    public function sendMessage(string $sessionId, string $content, ?User $user): array
    {
        // 1. Megkeressük az adott Chat-et
        $chat = $this->chatRepository->findBySessionId($sessionId);

        $message = $this->chatRepository->addMessage($chat, [
            'content' => $content,
            'type' => 'text',
            'sender_id' => $user?->id,
        ]);

        $message->load(['sender', 'chat']);
        $responseMessages = [$message];

        // Értesítjük a frontendet Websocket-en (ha használva van) a felhasználó üzenetéről
        // A .toOthers() megakadályozza, hogy a küldő duplán kapja meg (API válasz + Websocket)
        broadcast(new NewChatMessage($message))->toOthers();

        // Ha a bot kezeli épp a chat-et (nyitott állapotban van)
        if ($chat->status === 'open') {
            $userContentLower = strtolower(trim($content));

            // Ha üres üzenetet küld az ember, kitérünk
            if (empty($userContentLower)) {
                $chat->touch();
                return $responseMessages;
            }

            // Check if guest is requesting human help - if so, skip Ollama and escalate
            if ($this->isHumanHelpRequested($userContentLower)) {
                $this->escalate($sessionId);
                $escalationMessage = $this->chatRepository->addMessage($chat, [
                    'content' => 'I\'m connecting you with a human agent. Please wait a moment...',
                    'type' => 'system'
                ]);
                $escalationMessage->load(['chat', 'sender']);
                broadcast(new NewChatMessage($escalationMessage))->toOthers();
                $responseMessages[] = $escalationMessage;
                $chat->touch();
                return $responseMessages;
            }

            // 2. GY.I.K. Kontextus felépítése (RAG)
            // Lekérjük az összes kulcsszót az adatbázisból
            $faqs = $this->faqRepository->getAll();
            $context = "Available answers based on keywords:\n";
            foreach ($faqs as $faq) {
                $context .= "Keyword/Question part: {$faq->question} -> Answer: {$faq->answer}\n";
            }

            // 3. Korábbi utolsó 6 üzenet lekérése, hogy "emlékezzen" a kontextusra az AI
            $recentMessages = $chat->messages()->orderBy('created_at', 'desc')->take(6)->get()->reverse();
            $history = [];
            foreach ($recentMessages as $msg) {
                // Ensure recent messages don't include the exact one we just inserted, though take(6) will include it
                if ($msg->id === $message->id) {
                    continue; // exclude the current message from history because it is used for prompt
                }
                $history[] = [
                    'type' => $msg->type,
                    'content' => $msg->content,
                ];
            }

            // 4. A válasz generáltatása a helyi LLM (Ollama) segítségével
            $botResponse = $this->ollamaService->generateResponse($content, $context, $history);

            // 5. Ha van visszaadható válasz, eltároljuk a bot nevében és értesítjük a megrendelőt (Guest)
            if ($botResponse) {
                $botMessage = $this->chatRepository->addMessage($chat, ['content' => $botResponse, 'type' => 'bot']);
                $botMessage->load(['chat', 'sender']);
                broadcast(new NewChatMessage($botMessage))->toOthers();
                $responseMessages[] = $botMessage;
            }
        }

        // Frissítjük a chat utolsó használati (updated_at) idejét
        $chat->touch();

        return $responseMessages;
    }

    /**
     * Amikor a rendszer vagy a látogató kéri a bot kikapcsolását
     * és az elő élő ember ("Ügynök" / human help) értesítését
     */
    public function escalate(string $sessionId): Chat
    {
        $chat = $this->chatRepository->findBySessionId($sessionId);

        // Csak akkor vonjuk be az embert, ha eddig a bot kezelte a logikát
        if ($chat->status === 'open') {
            $this->chatRepository->update($chat, ['status' => 'pending_agent']);
            $systemMessage = $this->chatRepository->addMessage($chat, ['content' => 'Agents have been notified. Please wait patiently...', 'type' => 'system']);

            broadcast(new ChatStatusUpdated($chat))->toOthers();
            broadcast(new NewChatMessage($systemMessage))->toOthers();
        }

        return $chat;
    }

    /**
     * Check if the user is requesting human help based on keywords
     */
    protected function isHumanHelpRequested(string $message): bool
    {
        $humanHelpKeywords = [
            'human',
            'agent',
            'person',
            'help',
            'support',
            'talk to human',
            'real person',
            'customer service',
            'representative',
            'staff',
            'escalate',
            'human help',
            'live agent',
            'talk to someone',
            'i need help',
            'need assistance',
            'contact support',
            'speak to human',
            'real agent',
            'human assistant',
            'live support',
            'help desk',
            'support team',
            'customer support',
            'technical support',
            'billing support',
            'sales support',
            'complaint',
            'issue',
            'problem',
            'urgent',
            'emergency',
            'manager',
            'supervisor',
            'complaints',
            'feedback',
            'report issue',
            'file complaint',
            'speak to manager'
        ];

        foreach ($humanHelpKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
