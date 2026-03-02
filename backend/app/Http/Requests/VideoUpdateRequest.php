<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Mapping\VideoMapping;

class VideoUpdateRequest extends FormRequest
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
                . VideoMapping::MODEL_TABLE_NAME . ',' 
                . VideoMapping::UUID . ',' 
                . VideoMapping::DELETED_AT . ',NULL',

            'titulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'O vídeo é obrigatório.',
            'uuid.exists' => 'O vídeo informado não existe ou foi removido.',
            'titulo.max' => 'O título deve ter no máximo 255 caracteres.',
            'descricao.max' => 'A descrição deve ter no máximo 1000 caracteres.',
        ];
    }
}