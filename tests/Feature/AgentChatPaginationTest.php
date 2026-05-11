<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Chat;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentChatPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function getAgent()
    {
        return User::factory()->create(['role' => 'agent']);
    }

    public function test_agent_chat_list_returns_paginated_data()
    {
        $agent = $this->getAgent();

        // Create more than 10 chats to test pagination
        Chat::factory()->count(20)->create(['status' => 'open', 'agent_id' => null]);

        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats?page=1');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'current_page',
                     'last_page',
                     'per_page',
                     'total',
                     'links'
                 ]);

        // Should return 10 items per page (from CHAT_PAGINATION_PER_PAGE=10)
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
        $this->assertEquals(20, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }

    public function test_agent_chat_list_pagination_second_page()
    {
        $agent = $this->getAgent();

        // Create 20 chats to test pagination
        Chat::factory()->count(20)->create(['status' => 'open', 'agent_id' => null]);

        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats?page=2');

        $response->assertStatus(200);

        // Second page should have 10 items (20 total - 10 first page)
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(2, $response->json('current_page'));
        $this->assertEquals(20, $response->json('total'));
    }

    public function test_agent_chat_list_default_page_is_one()
    {
        $agent = $this->getAgent();

        Chat::factory()->count(5)->create(['status' => 'open', 'agent_id' => null]);

        $response = $this->actingAs($agent, 'api')->getJson('/api/agent/chats');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('current_page'));
    }
}
