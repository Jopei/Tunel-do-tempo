<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriaResumoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao_curta' => $this->descricao_curta,
            'tipo_historia' => $this->tipo?->nome,
            'data_historia' => $this->data_historia
                ? Carbon::parse($this->data_historia)->toDateString()
                : null,
            'fotos_count' => $this->contagem('fotos'),
            'videos_count' => $this->contagem('videos'),
            'musicas_count' => $this->contagem('musicas'),

            'usuarios' => $this->usuarios->map(fn ($usuario) => [
                'uuid' => $usuario->uuid,
                'nome' => $usuario->nome,
            ]),
        ];
    }

    private function contagem(string $relacao): int
    {
        $atributo = $relacao . '_count';

        if (array_key_exists($atributo, $this->resource->getAttributes())) {
            return (int) $this->resource->getAttribute($atributo);
        }

        return $this->resource->{$relacao}()->whereNull($relacao . '.deleted_at')->count();
    }
}
