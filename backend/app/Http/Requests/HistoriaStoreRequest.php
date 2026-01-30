<?php

namespace App\Http\Requests;

use App\Enums\TipoHistoriaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoriaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao_curta' => ['required', 'string', 'max:500'],
            'conteudo' => ['required', 'string'],
            'data_historia' => ['required', 'date'],
            'tipo_historia' => ['required', Rule::in(TipoHistoriaEnum::values())],

            'usuarios' => ['nullable', 'array'],
            'usuarios.*' => ['uuid'],

            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png'],

            'videos' => ['nullable', 'array'],
            'videos.*' => ['file', 'mimes:mp4,mov,avi'],

            'musicas' => ['nullable', 'array'],
            'musicas.*' => ['file', 'mimes:mp3,wav,ogg'],
        ];
    }
}
