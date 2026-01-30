<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:8'],
            'tipo_usuario_id' => ['required', 'integer', 'exists:tipo_usuario,id'],
            'aniversario' => ['nullable', 'date'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório.',
            'email.required' => 'Email é obrigatório.',
            'email.unique' => 'Email já cadastrado.',
            'senha.required' => 'Senha é obrigatória.',
            'tipo_usuario_id.required' => 'Tipo de usuário é obrigatório.',
        ];
    }
}
