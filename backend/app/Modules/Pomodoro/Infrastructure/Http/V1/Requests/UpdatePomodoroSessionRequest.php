<?php

declare(strict_types=1);

namespace App\Modules\Pomodoro\Infrastructure\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePomodoroSessionRequest extends FormRequest
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
            'current_status' => ['required', 'string', 'in:work,break,long_break,paused,finished'],
            'current_cycle' => ['required', 'integer', 'min:1'],
        ];
    }
}
