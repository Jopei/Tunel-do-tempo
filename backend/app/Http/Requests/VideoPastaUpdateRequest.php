<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoPastaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'pai_uuid' => ['nullable', 'uuid', Rule::exists('video_pastas', 'uuid')->whereNull('deleted_at')],
        ];
    }
}
