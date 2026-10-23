<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MusicaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'musica' => ['required', 'file', 'mimes:mp3,wav,ogg', 'max:20480'],
            'artista' => ['nullable', 'string', 'max:255'],
            'album' => ['nullable', 'string', 'max:255'],
            'ano' => ['nullable', 'integer', 'between:1900,2100'],
            'duracao_segundos' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'capa' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096', 'dimensions:max_width=12000,max_height=12000'],
            'capa_foto_uuid' => ['nullable', 'uuid', Rule::exists('fotos', 'uuid')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título da música é obrigatório.',
            'musica.required' => 'O arquivo de música é obrigatório.',
            'musica.mimes' => 'Formato de música inválido. Use mp3, wav ou ogg.',
        ];
    }
}
