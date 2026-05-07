<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePomodoroSessionRequest extends FormRequest
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
            'settings' => ['nullable', 'array'],
            'settings.work_duration' => ['required_with:settings', 'integer', 'min:1'],
            'settings.break_duration' => ['required_with:settings', 'integer', 'min:1'],
            'settings.repeats_count' => ['required_with:settings', 'integer', 'min:1'],
            'settings.long_break_duration' => ['nullable', 'integer', 'min:1'],
            'settings.cycles_before_long_break' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
