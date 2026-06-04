<?php

declare(strict_types=1);

namespace App\Core\MailNotifier\Infrastructure\Services\MailNotifier;

use App\Core\MailNotifier\Domain\Contracts\MailNotifierApiClientInterface;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\MailNotifierResponse;
use App\Core\MailNotifier\Infrastructure\Services\MailNotifier\DTOs\SendEmailDTO;
use App\Modules\Plugin\Domain\Contracts\PluginExecutorInterface;
use Illuminate\Support\Facades\Config;

final class MailNotifierApiClient implements MailNotifierApiClientInterface
{
    public function __construct(
        private readonly PluginExecutorInterface $pluginExecutor,
    ) {}

    public function sendEmail(SendEmailDTO $dto): MailNotifierResponse
    {
        $smtp = $this->smtpConfig();
        $from = $this->fromConfig();

        $result = $this->pluginExecutor->execute('mail_notifier', 'send_email', [
            'host' => $smtp['host'],
            'port' => $smtp['port'],
            'username' => $smtp['username'],
            'password' => $smtp['password'],
            'encryption' => $smtp['encryption'],
            'from_address' => $from['address'],
            'from_name' => $from['name'],
            'to' => $dto->to,
            'subject' => $dto->subject,
            'body' => $dto->body,
            'is_html' => $dto->isHtml,
        ]);

        return new MailNotifierResponse(
            ok: (bool) ($result['ok'] ?? false),
            description: $this->stringOrNull($result, 'description'),
            messageId: $this->stringOrNull($result, 'message_id'),
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function stringOrNull(array $result, string $key): ?string
    {
        $value = $result[$key] ?? null;

        return match (true) {
            $value === null => null,
            is_string($value) => $value,
            is_scalar($value) => (string) $value,
            default => null,
        };
    }

    /**
     * @return array{host: string, port: int, username: string, password: string, encryption: string}
     */
    private function smtpConfig(): array
    {
        /** @var array{host?: string|int, port?: string|int, username?: string, password?: string, encryption?: string} $raw */
        $raw = (array) Config::get('mail.mailers.smtp', []);

        return [
            'host' => (string) ($raw['host'] ?? ''),
            'port' => (int) ($raw['port'] ?? 587),
            'username' => (string) ($raw['username'] ?? ''),
            'password' => (string) ($raw['password'] ?? ''),
            'encryption' => (string) ($raw['encryption'] ?? ''),
        ];
    }

    /**
     * @return array{address: string, name: string}
     */
    private function fromConfig(): array
    {
        /** @var array{address?: string, name?: string} $raw */
        $raw = (array) Config::get('mail.from', []);

        return [
            'address' => (string) ($raw['address'] ?? ''),
            'name' => (string) ($raw['name'] ?? ''),
        ];
    }
}
