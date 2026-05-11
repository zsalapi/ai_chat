<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// RRESTful (Resource) Kontroller az Események kezeléséhez
class EventController extends Controller
{
    // A logikát (Service Pattern) egy külön Esemény Szolgáltatásba (EventService) szerveztük ki,
    // hogy ez a Controller fájl tiszta, átlátható és vékony maradjon ("Fat Models, Thin Controllers").
    protected EventService $eventService;

    // Dependency Injection (Függőség-befecskendezés): A Laravel automatikusan átadja a Service példányt.
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;

        // API Rate Limiting (Kérés Limitálás) - Alapból 60 kérés/perc/IP cím a felesleges terhelés/DDoS elkerülése végett.
        $this->middleware('throttle:api');
    }

    /**
     * Összes esemény lekérése (GET /api/events)
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->eventService->getEvents(auth('api')->user());
        return response()->json([
            'success' => true,
            'message' => 'Events retrieved successfully',
            'data' => $events
        ], 200);
    }

    /**
     * Új esemény létrehozása (POST /api/events)
     * Az EventStoreRequest egy un. Form Request Validator (Külön fájl ellenőrzi a bejövő HTTP kérést!)
     */
    public function store(EventStoreRequest $request): JsonResponse
    {
        // A ->validated() csak azt adja át, ami sikeresen átment a biztonsági szűrőkön.
        $event = $this->eventService->createEvent(auth('api')->user(), $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201); // 201 - Created status kód
    }

    /**
     * Meglévő esemény módosítása (PUT/PATCH /api/events/{id})
     */
    public function update(EventUpdateRequest $request, int $id): JsonResponse
    {
        $event = $this->eventService->updateEvent(auth('api')->user(), $id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ], 200);
    }

    /**
     * Meglévő esemény törlése (DELETE /api/events/{id})
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->eventService->deleteEvent(auth('api')->user(), $id);
        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
            'data' => []
        ], 200);
    }
}
