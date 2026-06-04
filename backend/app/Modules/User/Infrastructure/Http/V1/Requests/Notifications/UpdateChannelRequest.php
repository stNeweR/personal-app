<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', 'in:telegram,email'],
        ];
    }
}
