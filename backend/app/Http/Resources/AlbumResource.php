<?php

namespace App\Http\Resources;

use App\Mapping\AlbumMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Capa soft-deleted conta como ausente (cai no fallback da primeira foto).
        $capa = $this->capa && !$this->capa->trashed() ? $this->capa : null;
        $capa ??= $this->relationLoaded('primeiraFoto') ? $this->primeiraFoto : $this->fotos()->first();

        // Anos extremos das fotos (data_foto; sem data_foto a foto nao conta). Lista traz agregados; demais usos consultam.
        if (array_key_exists('data_min', $this->resource->getAttributes())) {
            [$min, $max] = [$this->data_min, $this->data_max];
        } else {
            $min = $this->fotos()->whereNull('fotos.deleted_at')->min('fotos.data_foto');
            $max = $this->fotos()->whereNull('fotos.deleted_at')->max('fotos.data_foto');
        }

        return [
            'uuid' => $this->{AlbumMapping::UUID},
            'titulo' => $this->{AlbumMapping::TITULO},
            'descricao' => $this->{AlbumMapping::DESCRICAO},
            'fotos_count' => (int) ($this->fotos_count ?? $this->fotos()->count()),
            'capa' => $capa ? [
                'uuid' => $capa->uuid,
                'url' => route('api.fotos.show', $capa->uuid),
                'thumbnail_url' => $capa->thumbnail_path ? asset('storage/' . $capa->thumbnail_path) : null,
            ] : null,
            'ano_inicio' => $min ? (int) substr((string) $min, 0, 4) : null,
            'ano_fim' => $max ? (int) substr((string) $max, 0, 4) : null,
            'created_at' => $this->{AlbumMapping::CREATED_AT},
            'updated_at' => $this->{AlbumMapping::UPDATED_AT},
        ];
    }
}
