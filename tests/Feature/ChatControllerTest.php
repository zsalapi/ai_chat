<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_start_chat_as_guest()
    {
        $response = $this->postJson('/api/chat/start');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'session_id',
            'chat' => ['id', 'session_id', 'status']
        ]);
        $this->assertEquals(true, $response->json('success'));
    }

    public function test_can_get_messages_for_chat()
    {
        $startResponse = $this->postJson('/api/chat/start');
        $sessionId = $startResponse->json('session_id');

        $response = $this->getJson("/api/chat/$sessionId/messages");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'messages',
            'chat'
        ]);
    }

    public function test_can_send_message_in_chat()
    {
        $startResponse = $this->postJson('/api/chat/start');
        $sessionId = $startResponse->json('session_id');

        $data = [
            'content' => 'Hello, world!'
        ];

        $response = $this->postJson("/api/chat/$sessionId/message", $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'messages'
        ]);
    }

    public function test_can_escalate_chat()
    {
        $startResponse = $this->postJson('/api/chat/start');
        $sessionId = $startResponse->json('session_id');

        $response = $this->postJson("/api/chat/$sessionId/escalate");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $this->assertEquals('pending_agent', $response->json('chat.status'));
    }
}
