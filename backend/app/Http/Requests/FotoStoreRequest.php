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
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096', 'dimensions:max_width=12000,max_height=12000'],
            'tipo_imagem_id' => ['required', 'exists:tipo_imagens,id'],
            'usuarios_uuid' => ['nullable', 'array'],
            'usuarios_uuid.*' => ['exists:usuarios,uuid'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'criado_em' => ['date'],
            'data_foto' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'imagem.required' => 'Imagem é obrigatória.',
            'imagem.image' => 'Arquivo inválido.',
            'imagem.mimes' => 'Formatos permitidos: jpg, jpeg, png.',
            'imagem.dimensions' => 'A imagem excede 12000 px por lado.',
            'tipo_imagem_id.required' => 'Tipo da imagem é obrigatório.',
            'tipo_imagem_id.exists' => 'Tipo da imagem inválido.',
            
        ];
    }
}
