<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlbumStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fotoExiste = Rule::exists('fotos', 'uuid')->whereNull('deleted_at');

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'capa_foto_uuid' => ['nullable', 'uuid', $fotoExiste],
            'fotos' => ['nullable', 'array', 'max:500'],
            'fotos.*' => ['uuid', 'distinct', $fotoExiste],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título do álbum é obrigatório.',
            'capa_foto_uuid.exists' => 'Foto de capa inválida.',
            'fotos.*.exists' => 'Uma ou mais fotos informadas não existem.',
        ];
    }
}
