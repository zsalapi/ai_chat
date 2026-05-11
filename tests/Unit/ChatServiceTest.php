<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\User;
use App\Repositories\ChatRepositoryInterface;
use App\Repositories\FaqRepositoryInterface;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\ChatStatusUpdated;
use App\Events\NewChatMessage;

class ChatServiceTest extends TestCase
{
    protected $chatRepository;
    protected $faqRepository;
    protected $ollamaService;
    protected $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock objektumok létrehozása a függőségek szimulálásához (nem az igazi adatbázist és API-t hívjuk)
        $this->chatRepository = Mockery::mock(ChatRepositoryInterface::class);
        $this->faqRepository = Mockery::mock(FaqRepositoryInterface::class);
        $this->ollamaService = Mockery::mock(\App\Services\OllamaService::class);
        
        // A tesztelendő szerviz példányosítása a mockolt függőségekkel
        $this->chatService = new ChatService($this->chatRepository, $this->faqRepository, $this->ollamaService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Teszteli, hogy a chat megfelelően elindul-e egy új session azonosítóval.
     */
    public function test_start_chat()
    {
        // IP cím és UUID szimulálása a kérésben
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        
        $chat = Mockery::mock(Chat::class)->makePartial();
        $chat->shouldReceive('load')->once()->with(['agent'])->andReturn($chat);
        
        // Ellenőrizzük, hogy meghívódik-e a create metódus a repozitóriumban
        $this->chatRepository->shouldReceive('create')
            ->once()
            ->andReturn($chat);
            
        // Ellenőrizzük, hogy bekerül-e az alapértelmezett üdvözlő üzenet
        $this->chatRepository->shouldReceive('addMessage')
            ->once()
            ->with($chat, Mockery::type('array'))
            ->andReturn(Mockery::mock(\App\Models\Message::class));

        $result = $this->chatService->start($request, null);

        $this->assertEquals($chat, $result);
    }

    /**
     * Teszteli az ügynök bekérését (eszkaláció), amikor a bot már nem tud válaszolni.
     */
    public function test_escalate_chat()
    {
        // Események (broadcast) figyelmen kívül hagyása a teszt alatt
        Event::fake([ChatStatusUpdated::class, NewChatMessage::class]);
        
        $chat = new Chat();
        $chat->status = 'open';
        
        $this->chatRepository->shouldReceive('findBySessionId')
            ->once()
            ->with('session-id')
            ->andReturn($chat);
            
        // Annak ellenőrzése, hogy az állapot átíródik-e 'pending_agent'-re
        $this->chatRepository->shouldReceive('update')
            ->once()
            ->with($chat, ['status' => 'pending_agent'])
            ->andReturn(true);
            
        // Rendszerüzenet hozzáadása az eszkalációról
        $this->chatRepository->shouldReceive('addMessage')
            ->once()
            ->with($chat, Mockery::type('array'))
            ->andReturn(Mockery::mock(\App\Models\Message::class));

        $result = $this->chatService->escalate('session-id');

        $this->assertEquals($chat, $result);
    }

    /**
     * Ez a legfontosabb teszt: Ellenőrzi a RAG folyamatot és az Ollama kapcsolatot.
     */
    public function test_send_message_triggers_ollama()
    {
        Event::fake([NewChatMessage::class]);

        // Mock chat példány létrehozása a segédfüggvénnyel
        $chat = $this->createMockChat('open', 1);
        
        $this->chatRepository->shouldReceive('findBySessionId')
            ->once()
            ->with('session-id')
            ->andReturn($chat);

        $userMessage = $this->createMockMessage(100, 'Hello', 'text');

        // Ellenőrizzük, hogy a felhasználó üzenete elmentésre kerül-e
        $this->chatRepository->shouldReceive('addMessage')
            ->once()
            ->with($chat, Mockery::on(fn($args) => $args['type'] === 'text' && $args['content'] === 'Hello'))
            ->andReturn($userMessage);

        // Szimuláljuk, hogy lekérjük a RAG kontextust (GY.I.K. elemek) az adatbázisból
        $this->faqRepository->shouldReceive('getAll')
            ->once()
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([
                (object)['question' => 'time', 'answer' => '10:00 AM']
            ]));

        // Szimuláljuk az Ollama (LLM) válaszadását
        $this->ollamaService->shouldReceive('generateResponse')
            ->once()
            ->with('Hello', Mockery::type('string'), [])
            ->andReturn('Hello from AI');

        $botMessage = $this->createMockMessage(101, 'Hello from AI', 'bot');

        // Ellenőrizzük, hogy az AI válasza is mentésre kerül-e a chat-be
        $this->chatRepository->shouldReceive('addMessage')
            ->once()
            ->with($chat, Mockery::on(fn($args) => $args['type'] === 'bot' && $args['content'] === 'Hello from AI'))
            ->andReturn($botMessage);

        $result = $this->chatService->sendMessage('session-id', 'Hello', null);

        // A válasznak 2 üzenetet kell tartalmaznia (felhasználóé + gépé)
        $this->assertCount(2, $result);
        $this->assertEquals($userMessage, $result[0]);
        $this->assertEquals($botMessage, $result[1]);
    }

    private function createMockChat(string $status, int $id)
    {
        /** @var \Mockery\MockInterface|Chat $chat */
        $chat = Mockery::mock(Chat::class)->makePartial();
        $chat->setAttribute('status', $status);
        $chat->setAttribute('id', $id);
        $chat->shouldReceive('touch')->andReturnTrue();

        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('orderBy')->andReturnSelf();
        $queryMock->shouldReceive('take')->andReturnSelf();
        $queryMock->shouldReceive('get')->andReturn(collect([]));
        $queryMock->shouldReceive('reverse')->andReturn(collect([]));
        
        $chat->shouldReceive('messages')->andReturn($queryMock);

        return $chat;
    }

    private function createMockMessage(int $id, string $content, string $type)
    {
        /** @var \Mockery\MockInterface|\App\Models\Message $msg */
        $msg = Mockery::mock(\App\Models\Message::class)->makePartial();
        $msg->setAttribute('id', $id);
        $msg->setAttribute('content', $content);
        $msg->setAttribute('type', $type);
        $msg->shouldReceive('load')->andReturnSelf();
        return $msg;
    }
}
