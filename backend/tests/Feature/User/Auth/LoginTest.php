<?php

declare(strict_types=1);

namespace Tests\Feature\User\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginUrl = '/api/v1/auth/login';
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
