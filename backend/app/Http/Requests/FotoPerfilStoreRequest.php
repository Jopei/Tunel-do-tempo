<?php

namespace App\Http\Requests;

use App\Enums\TipoUsuarioEnum;
use Illuminate\Foundation\Http\FormRequest;

class FotoPerfilStoreRequest extends FormRequest
{
    /** Apenas o proprio usuario (ou MEMBRO_ADM) troca a foto de perfil. */
    public function authorize(): bool
    {
        $logado = $this->user();

        if (!$logado) {
            return false;
        }

        return $logado->uuid === $this->input('usuario_uuid')
            || $logado->tipoUsuario?->nome === TipoUsuarioEnum::MEMBRO_ADM->value;
    }

    public function rules(): array
    {
        return [
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=12000,max_height=12000'],
            'usuario_uuid' => ['required', 'exists:usuarios,uuid'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:1000'],
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
