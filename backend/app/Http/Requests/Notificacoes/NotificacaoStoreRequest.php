<?php

namespace App\Http\Requests\Notificacoes;

use Illuminate\Foundation\Http\FormRequest;

class NotificacaoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tema' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título é obrigatório.',
            'titulo.max' => 'O título pode ter no máximo 255 caracteres.',
            'descricao.string' => 'A descrição deve ser um texto válido.',
            'tema.max' => 'O tema pode ter no máximo 255 caracteres.',
            'link.max' => 'O link pode ter no máximo 255 caracteres.',
        ];
    }
}