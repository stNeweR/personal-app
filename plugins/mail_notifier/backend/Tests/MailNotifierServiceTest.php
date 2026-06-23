<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Tests;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\Pomodoro\Application\Events\PomodoroPhaseChangedEvent;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Plugins\MailNotifier\Models\MailNotifierCredential;
use Plugins\MailNotifier\Models\MailNotifierHistory;
use Plugins\MailNotifier\Services\MailNotifierService;
use Tests\TestCase;

final class MailNotifierServiceTest extends TestCase
{
    use RefreshDatabase;

    private MailNotifierService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Plugin::updateOrCreate(
            ['name' => 'mail_notifier'],
            ['version' => '1.0.0', 'enabled' => true],
        );

        require_once base_path('plugins/mail_notifier/backend/Plugin.php');

        $plugin = new \Plugins\MailNotifier\Plugin;
        $plugin->register();
        $plugin->boot();

        $this->service = app(MailNotifierService::class);
    }

    public function test_is_connected_returns_true_when_email_set(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->assertTrue($this->service->isConnected($user));
    }

    public function test_is_connected_returns_false_when_email_null(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->assertFalse($this->service->isConnected($user));
    }

    public function test_is_connected_returns_false_when_email_empty(): void
    {
        $user = User::factory()->create(['email' => '']);

        $this->assertFalse($this->service->isConnected($user));
    }

    public function test_is_verified_returns_true_when_email_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->assertTrue($this->service->isVerified($user));
    }

    public function test_is_verified_returns_false_when_email_not_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->assertFalse($this->service->isVerified($user));
    }

    public function test_connect_saves_email_and_clears_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->service->connect($user, 'new@example.com');

        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_connect_does_not_set_notification_channel(): void
    {
        $user = User::factory()->create([
            'notification_channel' => 'telegram',
        ]);

        $this->service->connect($user, 'new@example.com');

        $user->refresh();
        $this->assertEquals('telegram', $user->notification_channel);
    }

    public function test_disconnect_clears_email_and_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->service->disconnect($user);

        $user->refresh();
        $this->assertNull($user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_disconnect_deletes_history(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        MailNotifierHistory::create([
            'user_id' => $user->id,
            'to' => 'test@example.com',
            'subject' => 'Test',
            'body' => 'Body',
        ]);

        $this->assertDatabaseHas('mail_notifier_history', ['user_id' => $user->id]);

        $this->service->disconnect($user);

        $this->assertDatabaseMissing('mail_notifier_history', ['user_id' => $user->id]);
    }

    public function test_send_raw_sends_email(): void
    {
        Mail::fake();

        $result = $this->service->sendRaw('to@example.com', 'Subject', 'Body');

        $this->assertTrue($result);
        Mail::assertSentTo('to@example.com', fn ($m) => $m->hasSubject('Subject'));
    }

    public function test_send_raw_returns_false_on_failure(): void
    {
        Mail::fake();
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP error'));

        $result = $this->service->sendRaw('to@example.com', 'Subject', 'Body');

        $this->assertFalse($result);
    }

    public function test_send_verification_email_creates_credential_and_sends_code(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->service->sendVerificationEmail($user);

        $this->assertDatabaseHas('mail_notifier_credentials', ['user_id' => $user->id]);
        Mail::assertSentTo('test@example.com');
    }

    public function test_send_verification_email_throws_when_email_not_set(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email not set');

        $this->service->sendVerificationEmail($user);
    }

    public function test_verify_email_verifies_correct_code(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $code = strtoupper(bin2hex(random_bytes(3)));

        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString($code),
            'token_expires_at' => now()->addHour(),
        ]);

        $this->service->verifyEmail($user, $code);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('mail_notifier_credentials', ['user_id' => $user->id]);
    }

    public function test_verify_email_throws_on_invalid_code(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString('ABCDEF'),
            'token_expires_at' => now()->addHour(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid code');

        $this->service->verifyEmail($user, 'WRONG1');
    }

    public function test_verify_email_throws_when_no_verification_pending(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No verification pending');

        $this->service->verifyEmail($user, 'ABCDEF');
    }

    public function test_verify_email_throws_when_code_expired(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        MailNotifierCredential::create([
            'user_id' => $user->id,
            'verification_token' => Crypt::encryptString('ABCDEF'),
            'token_expires_at' => now()->subHour(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Code expired');

        $this->service->verifyEmail($user, 'ABCDEF');
    }

    public function test_handle_phase_changed_sends_email_for_work(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->createPomodoroSettings($user->id, work_duration: 25);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'break',
            newStatus: 'work',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertSentTo('test@example.com');
    }

    public function test_handle_phase_changed_sends_email_for_break(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->createPomodoroSettings($user->id, break_duration: 5);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'break',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertSentTo('test@example.com');
    }

    public function test_handle_phase_changed_sends_email_for_long_break(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->createPomodoroSettings($user->id, long_break_duration: 15);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'long_break',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertSentTo('test@example.com');
    }

    public function test_handle_phase_changed_sends_email_for_finished(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'finished',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertSentTo('test@example.com');
    }

    public function test_handle_phase_changed_skips_same_status(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'work',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertNothingSent();
    }

    public function test_handle_phase_changed_skips_paused_status(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'paused',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertNothingSent();
    }

    public function test_handle_phase_changed_skips_when_email_not_set(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => null,
            'email_verified_at' => null,
        ]);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'break',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertNothingSent();
    }

    public function test_handle_phase_changed_skips_when_email_not_verified(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $event = new PomodoroPhaseChangedEvent(
            userId: $user->id,
            oldStatus: 'work',
            newStatus: 'break',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertNothingSent();
    }

    public function test_handle_phase_changed_skips_when_user_not_found(): void
    {
        Mail::fake();

        $event = new PomodoroPhaseChangedEvent(
            userId: 999999,
            oldStatus: 'work',
            newStatus: 'break',
        );

        $this->service->handlePhaseChanged($event);

        Mail::assertNothingSent();
    }

    public function test_get_history_returns_records(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        MailNotifierHistory::create([
            'user_id' => $user->id,
            'to' => 'test@example.com',
            'subject' => 'Subject 1',
            'body' => 'Body 1',
        ]);
        MailNotifierHistory::create([
            'user_id' => $user->id,
            'to' => 'test@example.com',
            'subject' => 'Subject 2',
            'body' => 'Body 2',
        ]);

        $history = $this->service->getHistory($user);

        $this->assertCount(2, $history);
        $this->assertEquals('Subject 2', $history[0]['subject']);
    }

    public function test_get_history_returns_empty_for_user_without_history(): void
    {
        $user = User::factory()->create();

        $history = $this->service->getHistory($user);

        $this->assertCount(0, $history);
    }

    private function createPomodoroSettings(
        int $userId,
        ?int $work_duration = null,
        ?int $break_duration = null,
        ?int $long_break_duration = null,
    ): void {
        \App\Modules\Pomodoro\Infrastructure\Models\PomodoroSettings::create([
            'user_id' => $userId,
            'work_duration' => $work_duration ?? 25,
            'break_duration' => $break_duration ?? 5,
            'repeats_count' => 4,
            'long_break_duration' => $long_break_duration,
            'cycles_before_long_break' => 4,
        ]);
    }
}
