<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi', 'max:51200'],
            'external_link' => ['nullable', 'url'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'O título do vídeo é obrigatório.',
            'video.mimes' => 'Formato de vídeo inválido.',
            'external_link.url' => 'Link externo inválido.',
            'thumbnail.image' => 'Thumbnail inválida.',
        ];
    }
}
