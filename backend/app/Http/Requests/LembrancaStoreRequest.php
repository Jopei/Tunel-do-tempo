<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LembrancaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texto' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'texto.required' => 'Escreva sua lembrança.',
            'texto.min' => 'A lembrança deve ter ao menos 2 caracteres.',
            'texto.max' => 'A lembrança pode ter no máximo 2000 caracteres.',
        ];
    }
}
