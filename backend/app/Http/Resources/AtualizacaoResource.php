<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AtualizacaoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'conteudo_markdown' => $this->conteudo_markdown,
            'link_musica' => $this->link_musica,
            'usuario_nome' => $this->usuario?->nome,
            'created_at' => $this->created_at,
        ];
    }
}