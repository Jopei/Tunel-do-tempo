<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use App\Mapping\UsuarioMapping;
use Illuminate\Validation\Rule;

class AtualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'uuid' => $this->route('uuid')
        ]);
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|exists:' 
                . UsuarioMapping::MODEL_TABLE_NAME . ',' 
                . UsuarioMapping::UUID . ',' 
                . UsuarioMapping::DELETED_AT . ',NULL',

            UsuarioMapping::NOME => 'required|string|max:255',

            UsuarioMapping::EMAIL => [
                'required',
                'email',
                'max:255',
                // Sem whereNull(deleted_at): o indice unico do banco vale tambem para
                // usuarios excluidos (soft delete), entao eles tambem bloqueiam o e-mail.
                Rule::unique(UsuarioMapping::MODEL_TABLE_NAME, UsuarioMapping::EMAIL)
                    ->ignore($this->route('uuid'), UsuarioMapping::UUID),
            ],

            UsuarioMapping::SENHA => 'nullable|string|min:6',

            UsuarioMapping::ANIVERSARIO => 'nullable|date',

            UsuarioMapping::TELEFONE => 'nullable|string|max:20',

            UsuarioMapping::DESCRICAO => 'nullable|string|max:1000',
        ];
    }
}