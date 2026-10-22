<?php

namespace App\Http\Requests;

use App\Enums\TipoHistoriaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoriaResumoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::in(TipoHistoriaEnum::values())],
            'usuario' => ['nullable', 'uuid'],
            'ordem' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
