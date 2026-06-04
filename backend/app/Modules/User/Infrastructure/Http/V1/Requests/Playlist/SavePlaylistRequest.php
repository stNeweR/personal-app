<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Requests\Playlist;

use Illuminate\Foundation\Http\FormRequest;

final class SavePlaylistRequest extends FormRequest
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
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
        ];
    }
}
