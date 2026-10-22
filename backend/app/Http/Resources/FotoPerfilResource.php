<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** Foto de perfil recem-enviada: sem ids internos, path nem cadastrante. */
class FotoPerfilResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'url' => route('api.fotos.show', ['uuid' => $this->uuid, 'v' => $this->updated_at?->timestamp]),
            'thumbnail_url' => $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null,
            'largura' => $this->largura,
            'altura' => $this->altura,
        ];
    }
}
