<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendEmailRequest extends FormRequest
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
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
