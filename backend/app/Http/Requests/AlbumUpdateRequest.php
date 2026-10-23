<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlbumUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'capa_foto_uuid' => ['nullable', 'uuid', Rule::exists('fotos', 'uuid')->whereNull('deleted_at')],
        ];
    }
}
