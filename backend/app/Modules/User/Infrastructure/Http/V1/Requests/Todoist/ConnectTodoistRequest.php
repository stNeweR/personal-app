<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Requests\Todoist;

use Illuminate\Foundation\Http\FormRequest;

final class ConnectTodoistRequest extends FormRequest
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
            'api_token' => ['required', 'string', 'min:10'],
        ];
    }
}
