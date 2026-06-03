<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Requests\Todoist;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTodoistTaskRequest extends FormRequest
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
            'content' => ['required', 'string', 'max:500'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'priority' => ['sometimes', 'integer', 'between:1,4'],
        ];
    }
}
