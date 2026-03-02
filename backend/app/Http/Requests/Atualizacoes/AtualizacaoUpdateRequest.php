<?php

namespace App\Http\Requests\Atualizacoes;

use Illuminate\Foundation\Http\FormRequest;

class AtualizacaoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'conteudo_markdown' => 'required|string',
            'link_musica' => 'nullable|string|max:255',
        ];
    }
}