<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected string $telegramWebhookUrl;

    protected string $telegramUrl;

    protected const string SEND_MESSAGE_ENDPOINT = '/sendMessage';

    protected function setUp(): void
    {
        parent::setUp();

        $this->telegramWebhookUrl = config('telegram.application_webhook_endpoint');
        $this->telegramUrl = config('telegram.telegram_url');
        $this->botToken = config('telegram.telegram_bot_token');
    }
}
