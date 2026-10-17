<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacaoEnviadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'tema' => $this->tema,
            'link' => $this->link,
            'created_at' => $this->created_at,
            'autor' => $this->autor ? [
                'uuid' => $this->autor->uuid,
                'nome' => $this->autor->nome,
            ] : null,
            'lidas_count' => (int) ($this->lidas_count ?? 0),
        ];
    }
}
