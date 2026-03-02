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
        ];
    }
}