<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MusicaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'artista' => ['nullable', 'string', 'max:255'],
            'album' => ['nullable', 'string', 'max:255'],
            'ano' => ['nullable', 'integer', 'between:1900,2100'],
            'duracao_segundos' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'capa_foto_uuid' => ['nullable', 'uuid', Rule::exists('fotos', 'uuid')->whereNull('deleted_at')],
        ];
    }
}
