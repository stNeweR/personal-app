<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Tests;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Plugins\MailNotifier\Models\MailNotifierCredential;
use Tests\TestCase;

final class MailNotifierControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = '/api/v1/mail-notifier';

        Plugin::updateOrCreate(
            ['name' => 'mail_notifier'],
            ['version' => '1.0.0', 'enabled' => true],
        );

        require_once base_path('plugins/mail_notifier/backend/Plugin.php');

        $plugin = new \Plugins\MailNotifier\Plugin;
        $plugin->register();
        $plugin->boot();
    }

    public function test_guest_cannot_access_endpoints(): void
    {
        $this->getJson("{$this->baseUrl}/status")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/connect", ['email' => 'a@b.com'])->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/disconnect")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/send-verification")->assertUnauthorized();
        $this->postJson("{$this->baseUrl}/verify-email", ['code' => '123'])->assertUnauthorized();
    }

    public function test_status_returns_disconnected_when_no_email(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson([
                'connected' => false,
                'verified' => false,
                'email' => null,
            ]);
    }

    public function test_status_returns_connected_when_email_set(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'verified' => false,
                'email' => 'test@example.com',
            ]);
    }

    public function test_status_returns_verified_when_email_confirmed(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");

        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'verified' => true,
                'email' => 'test@example.com',
            ]);
    }

    public function test_connect_saves_email(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Email saved. Please verify your email.']);

        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_connect_validates_email_format(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Valid email is required']);
    }

    public function test_connect_rejects_empty_email(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Valid email is required']);
    }

    public function test_disconnect_clears_email(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/disconnect");

        $response->assertOk()
            ->assertJson(['message' => 'Mail notifier disconnected']);

        $user->refresh();
        $this->assertNull($user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_disconnect_does_not_set_notification_channel(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
            'notification_channel' => 'telegram',
        ]);

        $this->actingAs($user)->postJson("{$this->baseUrl}/disconnect");

        $user->refresh();
        $this->assertEquals('telegram', $user->notification_channel);
    }

    public function test_send_verification_sends_email(): void
    {
        Mail::fake();

        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/send-verification");

        $response->assertOk()
            ->assertJson(['message' => 'Verification email sent']);

        $this->assertDatabaseHas('mail_notifier_credentials', ['user_id' => $user->id]);
        Mail::assertSentTo('test@example.com');
    }

    public function test_send_verification_fails_when_no_email(): void
    {
        $user = User::factory()->apiUser()->create(['email' => null]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/send-verification");

        $response->assertBadRequest()
            ->assertJson(['message' => 'Email not set']);
    }

    public function test_verify_email_verifies_code(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
        ]);

        $code = 'ABCDEF';
        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString($code),
            'token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Email verified successfully']);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('mail_notifier_credentials', ['user_id' => $user->id]);
    }

    public function test_verify_email_rejects_invalid_code(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
        ]);

        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString('ABCDEF'),
            'token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => 'WRONG1',
        ]);

        $response->assertBadRequest()
            ->assertJson(['message' => 'Invalid code']);
    }

    public function test_verify_email_rejects_empty_code(): void
    {
        $user = User::factory()->apiUser()->create();

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Code is required']);
    }

    public function test_verify_email_rejects_expired_code(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
        ]);

        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString('ABCDEF'),
            'token_expires_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => 'ABCDEF',
        ]);

        $response->assertBadRequest()
            ->assertJson(['message' => 'Code expired']);
    }

    public function test_verify_email_rejects_when_no_pending(): void
    {
        $user = User::factory()->apiUser()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => 'ABCDEF',
        ]);

        $response->assertBadRequest()
            ->assertJson(['message' => 'No verification pending']);
    }

    public function test_full_flow_connect_then_verify(): void
    {
        Mail::fake();

        $user = User::factory()->apiUser()->create();

        $this->actingAs($user)->postJson("{$this->baseUrl}/connect", [
            'email' => 'flow@example.com',
        ])->assertOk();

        $user->refresh();
        $this->assertEquals('flow@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        $credential = MailNotifierCredential::where('user_id', $user->id)->first();
        $this->assertNotNull($credential);
        $code = Crypt::decryptString($credential->verification_token);

        $this->actingAs($user)->postJson("{$this->baseUrl}/verify-email", [
            'code' => $code,
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user)->getJson("{$this->baseUrl}/status");
        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'verified' => true,
                'email' => 'flow@example.com',
            ]);
    }
}
