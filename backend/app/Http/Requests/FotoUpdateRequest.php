<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.string' => 'O título deve ser um texto válido.',
            'titulo.max' => 'O título pode ter no máximo 255 caracteres.',
            'descricao.string' => 'A descrição deve ser um texto válido.',
        ];
    }
}
