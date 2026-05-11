<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

interface EventRepositoryInterface
{
    public function getForUser(User $user): Collection;
    public function createForUser(User $user, array $data): Event;
    public function findForUser(User $user, int $id): Event;
    public function delete(Event $event): bool;
}
