<?php

namespace App\Core\Telegram\Infrastructure\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TelegramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'update_id' => ['integer', 'required'],
            'message' => ['array'],
            'from' => ['array'],
            'chat' => ['array'],
            'date' => ['integer'],
            'text' => ['string'],
            'entities' => ['array'],
        ];
    }
}
