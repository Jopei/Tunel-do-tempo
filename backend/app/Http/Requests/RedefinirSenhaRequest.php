<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedefinirSenhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'senha' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'senha.min' => 'A senha deve ter ao menos 8 caracteres.',
        ];
    }
}
