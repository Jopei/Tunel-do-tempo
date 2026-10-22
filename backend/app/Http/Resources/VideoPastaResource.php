<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoPastaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'pai_uuid' => $this->pai?->uuid,
            'videos_count' => (int) ($this->videos_count ?? $this->videos()->count()),
        ];
    }
}
