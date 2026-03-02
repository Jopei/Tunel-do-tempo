<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriaShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao_curta' => $this->descricao_curta,
            'conteudo' => $this->conteudo,
            'data_historia' => $this->data_historia,
            'tipo_historia' => $this->tipo?->nome,

            'usuarios' => $this->usuarios->map(fn($u) => [
                'uuid' => $u->uuid,
                'nome' => $u->nome,
            ]),

            'fotos' => $this->fotos->pluck('uuid'),
            'videos' => $this->videos->pluck('uuid'),
            'musicas' => $this->musicas->pluck('uuid'),
        ];
    }
}
