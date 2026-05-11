<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentEventRepository implements EventRepositoryInterface
{
    public function getForUser(User $user): Collection
    {
        return $user->events()->orderBy('occurrence', 'desc')->get();
    }

    public function createForUser(User $user, array $data): Event
    {
        return $user->events()->create($data);
    }

    public function findForUser(User $user, int $id): Event
    {
        return $user->events()->findOrFail($id);
    }

    public function delete(Event $event): bool
    {
        return $event->delete();
    }
}
