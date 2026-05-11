<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChatRepositoryInterface
{
    public function create(array $data): Chat;
    public function findBySessionId(string $sessionId): Chat;
    public function findById(int $id): Chat;
    public function addMessage(Chat $chat, array $data): Message;
    public function getDashboardChats(User $agent, int $page = 1): LengthAwarePaginator;
    public function getClosedChats(): LengthAwarePaginator;
    public function update(Chat $chat, array $data): bool;
}
