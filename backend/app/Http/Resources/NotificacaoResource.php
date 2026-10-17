<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacaoResource extends JsonResource
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
            'lida' => (bool) ($this->lida ?? false),
        ];
    }
}
