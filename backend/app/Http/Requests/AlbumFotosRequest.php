<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlbumFotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fotos' => ['required', 'array', 'min:1', 'max:500'],
            'fotos.*' => ['uuid', 'distinct', Rule::exists('fotos', 'uuid')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'fotos.required' => 'Informe as fotos.',
            'fotos.*.exists' => 'Uma ou mais fotos informadas não existem.',
        ];
    }
}
