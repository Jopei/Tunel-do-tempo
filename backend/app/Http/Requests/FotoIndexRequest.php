<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FotoIndexRequest extends FormRequest
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
            'agrupar' => ['nullable', Rule::in(['data'])],
            'album_uuid' => ['nullable', 'uuid'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
        ];
    }
}
