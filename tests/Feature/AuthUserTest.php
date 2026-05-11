<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthUserTest extends TestCase
{
    use RefreshDatabase;


    public function test_can_login_user()
    {
       
        $user = User::factory()->create([
            'password' => bcrypt($password = 'password123')
        ]);

        $data = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(200); 
        $response->assertJsonFragment(['message' => 'Successfully logged in']);
    }

    public function test_cannot_login_with_invalid_credentials()
    {
        $data = [
            'email' => 'invalid@test.test',
            'password' => 'wrongp@55word',
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(401); 
        $response->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_can_logout_user()
    {

        $user = User::factory()->create();
        $token = $user->createToken('TestApp')->accessToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200); // OK
        $response->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_cannot_logout_without_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401); 
        $response->assertJsonFragment(['message' => 'Unauthenticated.']);
    }
}

