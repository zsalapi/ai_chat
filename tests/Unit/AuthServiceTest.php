<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Mockery;

class AuthServiceTest extends TestCase
{
    protected $userRepository;
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_creates_user()
    {
        $data = ['name' => 'John', 'email' => 'john@test.com', 'password' => 'secret'];
        $user = new User($data);

        $this->userRepository->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($user);

        $result = $this->authService->register($data);

        $this->assertEquals($user, $result);
    }

    public function test_login_returns_user_on_valid_credentials()
    {
        $password = 'secret';
        $credentials = ['email' => 'john@test.com', 'password' => $password];
        
        // Use make to just get a model instance without trying to hit DB if we don't need to.
        $user = new User(['id' => 1, 'email' => 'john@test.com', 'password' => Hash::make($password)]);

        $this->userRepository->shouldReceive('findUserEmail')
            ->once()
            ->with('john@test.com')
            ->andReturn($user);

        $result = $this->authService->login($credentials);

        $this->assertEquals($user, $result);
    }

    public function test_logout_calls_repository()
    {
        $user = new User(['id' => 1]);

        $this->userRepository->shouldReceive('logout')
            ->once()
            ->with($user);

        $this->authService->logout($user);
        
        // Assert true implicitly since Mockery will fail if logout isn't called
        $this->assertTrue(true);
    }
}
