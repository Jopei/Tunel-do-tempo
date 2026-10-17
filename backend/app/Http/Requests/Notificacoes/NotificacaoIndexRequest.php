<?php

namespace App\Http\Requests\Notificacoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificacaoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['todas', 'nao_lidas'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
