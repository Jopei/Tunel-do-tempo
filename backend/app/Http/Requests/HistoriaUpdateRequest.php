<?php
namespace App\Http\Requests;

use App\Enums\TipoHistoriaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoriaUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao_curta' => ['nullable', 'string'],
            'tipo_historia' => ['required', Rule::in(TipoHistoriaEnum::values())],
            'usuarios' => ['nullable', 'array'],
            'usuarios.*' => ['uuid'],
        ];
    }
}
