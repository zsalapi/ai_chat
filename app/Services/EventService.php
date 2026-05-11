<?php

namespace App\Services;

use App\Models\User;
use App\Models\Event;
use App\Repositories\EventRepositoryInterface;
use Illuminate\Support\Collection;

class EventService
{
    protected EventRepositoryInterface $eventRepository;

    public function __construct(EventRepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function getEvents(User $user): Collection
    {
        return $this->eventRepository->getForUser($user);
    }

    public function createEvent(User $user, array $data): Event
    {
        return $this->eventRepository->createForUser($user, $data);
    }

    public function updateEvent(User $user, int $id, array $data): Event
    {
        $event = $this->eventRepository->findForUser($user, $id);
        $event->update($data);
        return $event;
    }

    public function deleteEvent(User $user, int $id): bool
    {
        $event = $this->eventRepository->findForUser($user, $id);
        return $this->eventRepository->delete($event);
    }
}
