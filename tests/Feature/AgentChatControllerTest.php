<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getAgent()
    {
        return User::factory()->create(['role' => 'agent']);
    }

    protected function getCustomer()
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_agent_can_see_dashboard()
    {
        $agent = $this->getAgent();
        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_see_dashboard()
    {
        $customer = $this->getCustomer();
        $response = $this->actingAs($customer, 'api')->getJson('/api/agent/chats');

        $response->assertStatus(403);
    }

    public function test_agent_can_be_assigned_chat()
    {
        $agent = $this->getAgent();
        $chat = Chat::factory()->create(['status' => 'pending_agent']);

        $response = $this->actingAs($agent, 'api')->postJson("/api/agent/chats/{$chat->id}/assign");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Chat successfully assigned to you.']);
    }

    public function test_agent_can_close_assigned_chat()
    {
        $agent = $this->getAgent();
        $chat = Chat::factory()->create([
            'status' => 'assigned',
            'agent_id' => $agent->id
        ]);

        $response = $this->actingAs($agent, 'api')->postJson("/api/agent/chats/{$chat->id}/close");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Chat closed.']);
    }

    public function test_agent_cannot_close_unassigned_chat()
    {
        $agent1 = $this->getAgent();
        $agent2 = $this->getAgent();
        
        $chat = Chat::factory()->create([
            'status' => 'assigned',
            'agent_id' => $agent2->id
        ]);

        $response = $this->actingAs($agent1, 'api')->postJson("/api/agent/chats/{$chat->id}/close");

        $response->assertStatus(403);
    }
}
