<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoPerfilStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'usuario_uuid' => ['required', 'exists:usuarios,uuid'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'imagem.required' => 'Imagem é obrigatória.',
            'imagem.image' => 'Arquivo inválido.',
            'imagem.mimes' => 'Formatos permitidos: jpg, jpeg, png.',
            'usuario_uuid.required' => 'Usuário é obrigatório.',
        ];
    }
}
