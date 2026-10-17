<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'ano' => ['nullable', 'integer', 'between:1900,2100'],
            'usuario_uuid' => ['nullable', 'uuid'],
            'ordem' => ['nullable', Rule::in(['recentes', 'antigas', 'titulo'])],
        ];
    }
}
