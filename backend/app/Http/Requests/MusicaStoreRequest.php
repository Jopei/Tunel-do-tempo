<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
