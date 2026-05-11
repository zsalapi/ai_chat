<?php

namespace App\Services;

use App\Events\ChatStatusUpdated;
use App\Events\NewChatMessage;
use App\Models\Chat;
use App\Models\User;
use App\Repositories\ChatRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AgentChatService
{
    protected ChatRepositoryInterface $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }

    public function getDashboardChats(User $agent, int $page = 1)
    {
        return $this->chatRepository->getDashboardChats($agent, $page);
    }

    public function getChatMessages(Chat $chat)
    {
        return $chat->messages()->with('sender')->orderBy('created_at')->get();
    }

    public function sendMessage(Chat $chat, User $agent, string $content)
    {
        $chatWasAssigned = false;

        $message = DB::transaction(function () use ($chat, $content, $agent, &$chatWasAssigned) {
            $lockedChat = Chat::where('id', $chat->id)->lockForUpdate()->first();

            if ($lockedChat->agent_id && $lockedChat->agent_id !== $agent->id) {
                abort(409, 'Conflict! This conversation was just taken by another agent.');
            }

            if (!$lockedChat->agent_id) {
                $this->chatRepository->update($lockedChat, ['agent_id' => $agent->id, 'status' => 'assigned']);
                $chatWasAssigned = true;
            }

            return $this->chatRepository->addMessage($lockedChat, [
                'sender_id' => $agent->id,
                'content' => $content,
                'type' => 'text',
            ]);
        });

        $message->load(['chat', 'sender']);

        if ($chatWasAssigned) {
            broadcast(new ChatStatusUpdated($message->chat));
        }

        broadcast(new NewChatMessage($message));

        return $message;
    }

    public function assignChat(Chat $chat, User $agent)
    {
        $this->chatRepository->update($chat, ['agent_id' => $agent->id, 'status' => 'assigned']);
        $this->broadcastSystemMessage($chat, 'Agent (' . $agent->name . ') joined the conversation.');
    }

    public function closeChat(Chat $chat, User $agent)
    {
        if ($chat->agent_id !== $agent->id) {
            abort(403, 'You do not have permission to close this chat.');
        }

        $this->chatRepository->update($chat, ['status' => 'closed']);
        $this->broadcastSystemMessage($chat, 'The conversation has been closed by the agent (' . $agent->name . ').');
    }

    public function getClosedChats()
    {
        return $this->chatRepository->getClosedChats();
    }

    protected function broadcastSystemMessage(Chat $chat, string $content)
    {
        $message = $this->chatRepository->addMessage($chat, [
            'content' => $content,
            'type' => 'system',
        ]);

        broadcast(new ChatStatusUpdated($chat->fresh()));
        broadcast(new NewChatMessage($message));
    }
}
