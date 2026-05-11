<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatHumanHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_requests_human_help_with_keyword_human()
    {
        // Create a chat session
        $chat = Chat::factory()->create(['status' => 'open']);

        // Send message requesting human help
        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'I want to talk to a human'
        ]);

        $response->assertStatus(200);

        // Check that chat status changed to pending_agent
        $chat->refresh();
        $this->assertEquals('pending_agent', $chat->status);

        // Check that system message was added
        $messages = $response->json('messages');
        $systemMessage = collect($messages)->firstWhere('type', 'system');
        $this->assertNotNull($systemMessage);
        $this->assertStringContainsString('connecting you with a human agent', $systemMessage['content']);
    }

    public function test_guest_requests_human_help_with_keyword_agent()
    {
        $chat = Chat::factory()->create(['status' => 'open']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'Can I speak to an agent please?'
        ]);

        $response->assertStatus(200);

        $chat->refresh();
        $this->assertEquals('pending_agent', $chat->status);
    }

    public function test_guest_requests_human_help_with_keyword_help()
    {
        $chat = Chat::factory()->create(['status' => 'open']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'I need help with my issue'
        ]);

        $response->assertStatus(200);

        $chat->refresh();
        $this->assertEquals('pending_agent', $chat->status);
    }

    public function test_guest_requests_human_help_with_keyword_support()
    {
        $chat = Chat::factory()->create(['status' => 'open']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'I need customer support'
        ]);

        $response->assertStatus(200);

        $chat->refresh();
        $this->assertEquals('pending_agent', $chat->status);
    }

    public function test_normal_question_does_not_trigger_escalation()
    {
        $chat = Chat::factory()->create(['status' => 'open']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'What events do you have?'
        ]);

        $response->assertStatus(200);

        // Chat should remain open (not escalated)
        $chat->refresh();
        $this->assertEquals('open', $chat->status);

        // Should not contain system message
        $messages = $response->json('messages');
        $systemMessage = collect($messages)->firstWhere('type', 'system');
        $this->assertNull($systemMessage);
    }

    public function test_case_insensitive_human_help_detection()
    {
        $chat = Chat::factory()->create(['status' => 'open']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'I NEED TO SPEAK TO A HUMAN'
        ]);

        $response->assertStatus(200);

        $chat->refresh();
        $this->assertEquals('pending_agent', $chat->status);
    }

    public function test_already_escalated_chat_does_not_trigger_again()
    {
        // Create chat that's already pending_agent
        $chat = Chat::factory()->create(['status' => 'pending_agent']);

        $response = $this->postJson("/api/chat/{$chat->session_id}/message", [
            'content' => 'I need help'
        ]);

        $response->assertStatus(200);

        // Should not add another system message since chat is already escalated
        $messages = $response->json('messages');
        $systemMessage = collect($messages)->firstWhere('type', 'system');
        $this->assertNull($systemMessage);
    }
}
