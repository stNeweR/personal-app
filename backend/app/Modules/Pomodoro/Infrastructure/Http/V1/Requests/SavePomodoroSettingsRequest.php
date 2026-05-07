<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SavePomodoroSettingsRequest extends FormRequest
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
            'work_duration' => ['required', 'integer', 'min:1', 'max:120'],
            'break_duration' => ['required', 'integer', 'min:1', 'max:60'],
            'repeats_count' => ['required', 'integer', 'min:1', 'max:20'],
            'long_break_duration' => ['nullable', 'integer', 'min:1', 'max:120'],
            'cycles_before_long_break' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
