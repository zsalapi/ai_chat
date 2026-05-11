<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepositoryInterface;
use App\Services\EventService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class EventServiceTest extends TestCase
{
    protected $eventRepository;
    protected $eventService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->eventRepository = Mockery::mock(EventRepositoryInterface::class);
        $this->eventService = new EventService($this->eventRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_events()
    {
        $user = new User(['id' => 1]);
        $collection = new Collection([new Event(['id' => 1, 'title' => 'Test Event'])]);

        $this->eventRepository->shouldReceive('getForUser')
            ->once()
            ->with($user)
            ->andReturn($collection);

        $result = $this->eventService->getEvents($user);

        $this->assertEquals($collection, $result);
    }

    public function test_create_event()
    {
        $user = new User(['id' => 1]);
        $data = ['title' => 'New Event'];
        $event = new Event(['id' => 1] + $data);

        $this->eventRepository->shouldReceive('createForUser')
            ->once()
            ->with($user, $data)
            ->andReturn($event);

        $result = $this->eventService->createEvent($user, $data);

        $this->assertEquals($event, $result);
    }

    public function test_update_event()
    {
        $user = new User(['id' => 1]);
        $data = ['title' => 'Updated Event'];
        
        // Use Mockery on the real model to be able to assert the 'update' call
        $event = Mockery::mock(Event::class)->makePartial();
        $event->shouldReceive('update')
            ->once()
            ->with($data)
            ->andReturn(true);

        $this->eventRepository->shouldReceive('findForUser')
            ->once()
            ->with($user, 1)
            ->andReturn($event);

        $result = $this->eventService->updateEvent($user, 1, $data);

        $this->assertEquals($event, $result);
    }

    public function test_delete_event()
    {
        $user = new User(['id' => 1]);
        $event = new Event(['id' => 1]);

        $this->eventRepository->shouldReceive('findForUser')
            ->once()
            ->with($user, 1)
            ->andReturn($event);

        $this->eventRepository->shouldReceive('delete')
            ->once()
            ->with($event)
            ->andReturn(true);

        $result = $this->eventService->deleteEvent($user, 1);

        $this->assertTrue($result);
    }
}
