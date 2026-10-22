<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VideoPasta;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoIndexRequest extends FormRequest
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
            'ano' => ['nullable', 'integer', 'between:1900,2100'],
            'usuario_uuid' => ['nullable', 'uuid'],
            'ordem' => ['nullable', Rule::in(['recentes', 'antigas', 'titulo', 'titulo_desc', 'duracao', 'duracao_desc', 'tamanho', 'tamanho_desc', 'data_video', 'data_video_desc'])],
            'sem_data' => ['nullable', 'boolean'],
            'todas' => ['nullable', 'boolean'],
            'pasta_uuid' => ['nullable', 'string', function (string $atributo, mixed $valor, \Closure $falhar) {
                // "raiz" = videos sem pasta; senao precisa ser uuid de uma pasta existente.
                if ($valor !== 'raiz' && !(Str::isUuid($valor) && VideoPasta::where('uuid', $valor)->exists())) {
                    $falhar('Pasta inválida.');
                }
            }],
        ];
    }
}
