<?php

declare(strict_types=1);

namespace Plugins\Todoist\Http\Requests;

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
