<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoMoverPastaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // chave obrigatória; null = mover para a raiz
            'pasta_uuid' => ['present', 'nullable', 'uuid', Rule::exists('video_pastas', 'uuid')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'pasta_uuid.present' => 'Informe a pasta de destino (ou null para a raiz).',
            'pasta_uuid.exists' => 'Pasta de destino inválida.',
        ];
    }
}
