<?php

declare(strict_types=1);

namespace Plugins\YandexCalendar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConnectYandexCalendarRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'app_password' => ['required', 'string'],
        ];
    }
}
