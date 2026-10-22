<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoLoteExcluirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1', 'max:100'],
            'uuids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'uuids.required' => 'Informe as fotos a excluir.',
            'uuids.max' => 'Máximo de 100 fotos por exclusão em lote.',
            'uuids.*.uuid' => 'Identificador de foto inválido.',
        ];
    }
}
