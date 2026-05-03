<?php

declare(strict_types=1);

namespace Tests\Feature\User\Auth;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MeTest extends TestCase
{
    use RefreshDatabase;

    private string $meUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->meUrl = '/api/v1/auth/me';
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->apiUser()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($this->meUrl);

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson($this->meUrl);

        $response->assertUnauthorized();
    }
}
