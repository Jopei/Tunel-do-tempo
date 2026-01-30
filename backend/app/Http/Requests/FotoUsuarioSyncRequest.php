<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoUsuarioSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuarios_uuid' => ['nullable', 'array'],
            'usuarios_uuid.*' => ['exists:usuarios,uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuarios_uuid.array' => 'Usuários deve ser uma lista.',
            'usuarios_uuid.*.exists' => 'Um ou mais usuários informados não existem.',
        ];
    }
}
