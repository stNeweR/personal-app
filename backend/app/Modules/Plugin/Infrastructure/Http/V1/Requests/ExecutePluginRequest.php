<?php

declare(strict_types=1);

namespace App\Modules\Plugin\Infrastructure\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecutePluginRequest extends FormRequest
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
            'input' => ['sometimes', 'array'],
        ];
    }
}
