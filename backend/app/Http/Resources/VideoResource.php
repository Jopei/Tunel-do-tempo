<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'url' => route('api.videos.show', $this->uuid),
            'thumbnail' => $this->thumbnail
                ? asset('storage/' . $this->thumbnail)
                : null,
            'poster_url' => $this->thumbnail
                ? asset('storage/' . $this->thumbnail)
                : null,
            'pasta_uuid' => $this->pasta?->uuid,
            'duracao_segundos' => $this->duracao_segundos,
            'tamanho_bytes' => $this->tamanho_bytes,
            'data_video' => $this->data_video?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'cadastrado_por' => $this->cadastradoPor
                ? [
                    'uuid' => $this->cadastradoPor->uuid,
                    'nome' => $this->cadastradoPor->nome,
                    'slug' => $this->cadastradoPor->slug,
                    'foto_url' => MembroPublicoResource::fotoUrl($this->cadastradoPor),
                ]
                : null,
        ];
    }
}