<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'tipo_imagem_id' => ['required', 'exists:tipo_imagem,id'],
            'usuarios_uuid' => ['nullable', 'array'],
            'usuarios_uuid.*' => ['exists:usuarios,uuid'],
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
            'tipo_imagem_id.required' => 'Tipo da imagem é obrigatório.',
        ];
    }
}
