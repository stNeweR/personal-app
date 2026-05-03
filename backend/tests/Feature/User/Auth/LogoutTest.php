<?php

declare(strict_types=1);

namespace Tests\Feature\User\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private string $logoutUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logoutUrl = '/api/v1/auth/logout';
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->apiUser()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($this->logoutUrl);

        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson($this->logoutUrl);

        $response->assertUnauthorized();
    }
}
