<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Chat;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatPaginationConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function getAgent()
    {
        return User::factory()->create(['role' => 'agent']);
    }

    public function test_chat_pagination_uses_env_variable()
    {
        $agent = $this->getAgent();
        
        // Create 25 chats to test pagination with 10 per page
        Chat::factory()->count(25)->create(['status' => 'open', 'agent_id' => null]);
        
        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats?page=1');
        
        $response->assertStatus(200);
        
        // Should return 10 items per page (from CHAT_PAGINATION_PER_PAGE=10)
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(3, $response->json('last_page')); // 25 / 10 = 2.5 → 3 pages
    }

    public function test_closed_chats_pagination_uses_env_variable()
    {
        $agent = $this->getAgent();
        
        // Create 15 closed chats to test pagination
        Chat::factory()->count(15)->create(['status' => 'closed', 'agent_id' => $agent->id]);
        
        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/closed-chats?page=1');
        
        $response->assertStatus(200);
        
        // Should return 10 items per page
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
        $this->assertEquals(15, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }

    public function test_chat_pagination_second_page()
    {
        $agent = $this->getAgent();
        
        // Create 25 chats to test pagination
        Chat::factory()->count(25)->create(['status' => 'open', 'agent_id' => null]);
        
        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats?page=2');
        
        $response->assertStatus(200);
        
        // Second page should have 10 items
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('current_page'));
    }

    public function test_chat_pagination_third_page()
    {
        $agent = $this->getAgent();
        
        // Create 25 chats to test pagination
        Chat::factory()->count(25)->create(['status' => 'open', 'agent_id' => null]);
        
        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats?page=3');
        
        $response->assertStatus(200);
        
        // Third page should have 5 items (25 total - 10 first page - 10 second page)
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(3, $response->json('current_page'));
    }
}
