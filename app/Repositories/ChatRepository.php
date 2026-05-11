<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChatRepository implements ChatRepositoryInterface
{
    public function create(array $data): Chat
    {
        return Chat::create($data);
    }

    public function findBySessionId(string $sessionId): Chat
    {
        return Chat::where('session_id', $sessionId)->firstOrFail();
    }

    public function findById(int $id): Chat
    {
        return Chat::findOrFail($id);
    }

    public function addMessage(Chat $chat, array $data): Message
    {
        return $chat->messages()->create($data);
    }

    public function getDashboardChats(User $agent, int $page = 1): LengthAwarePaginator
    {
        $perPage = config('app.chat_pagination_per_page', 15);

        return Chat::whereIn('status', ['open', 'assigned', 'pending_agent'])
            ->where(function ($query) use ($agent) {
                $query->whereNull('agent_id')
                      ->orWhere('agent_id', $agent->id);
            })
            ->with(['messages' => function ($query) {
                $query->latest()->take(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getClosedChats(): LengthAwarePaginator
    {
        $perPage = config('app.chat_pagination_per_page', 15);

        return Chat::where('status', 'closed')
            ->with(['agent', 'messages' => function ($query) {
                $query->latest()->take(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    public function update(Chat $chat, array $data): bool
    {
        return $chat->update($data);
    }
}
