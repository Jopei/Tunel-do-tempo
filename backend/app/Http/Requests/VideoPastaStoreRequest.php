<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoPastaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'pai_uuid' => ['nullable', 'uuid', Rule::exists('video_pastas', 'uuid')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da pasta é obrigatório.',
            'pai_uuid.exists' => 'Pasta de destino inválida.',
        ];
    }
}
