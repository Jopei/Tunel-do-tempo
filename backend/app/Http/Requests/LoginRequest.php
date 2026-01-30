<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'senha' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'O campo email é obrigatório.',
            'login.string'   => 'O campo email deve ser um texto válido.',
            'senha.required' => 'O campo senha é obrigatório.',
            'senha.string'   => 'O campo senha deve ser um texto válido.',
        ];
    }
}
