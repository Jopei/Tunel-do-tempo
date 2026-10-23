<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TipoHistoriaEnum;
use App\Mapping\FotoMapping;
use Carbon\Carbon;

class FotoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->{FotoMapping::UUID},
            'titulo' => $this->{FotoMapping::TITULO},
            'descricao' => $this->{FotoMapping::DESCRICAO},
            'tipo_imagem_id' => $this->{FotoMapping::TIPO_IMAGEM_ID},
            'url' => route('api.fotos.show', $this->{FotoMapping::UUID}),
            'thumbnail_url' => $this->{FotoMapping::THUMBNAIL_PATH}
                ? asset('storage/' . $this->{FotoMapping::THUMBNAIL_PATH})
                : null,
            'data_foto' => $this->{FotoMapping::DATA_FOTO}?->toDateString(),
            'largura' => $this->{FotoMapping::LARGURA},
            'altura' => $this->{FotoMapping::ALTURA},
            'created_at' => $this->{FotoMapping::CREATED_AT},
            'cadastrado_por' => $this->when($request->user('sanctum') !== null, fn () => $this->cadastradoPor
                ? [
                    'uuid' => $this->cadastradoPor->uuid,
                    'nome' => $this->cadastradoPor->nome,
                    'slug' => $this->cadastradoPor->slug,
                    'foto_url' => MembroPublicoResource::fotoUrl($this->cadastradoPor),
                ]
                : null),
            'marcados' => $this->when(
                $request->user('sanctum') !== null && $this->relationLoaded('usuarios'),
                fn () => $this->usuarios
                    ->reject(fn ($u) => $u->trashed())
                    ->map(fn ($u) => [
                        'uuid' => $u->uuid,
                        'nome' => $u->nome,
                        'slug' => $u->slug,
                        'foto_url' => MembroPublicoResource::fotoUrl($u),
                    ])->values()
            ),
            'albuns' => $this->whenLoaded('albuns', fn () => $this->albuns
                ->map(fn ($a) => ['uuid' => $a->uuid, 'titulo' => $a->titulo])->values()),
            'historias' => $this->whenLoaded('historias', fn () => $this->historias
                ->reject(fn ($h) => $h->trashed() || $h->tipo?->nome === TipoHistoriaEnum::PESSOAL->value)
                ->map(fn ($h) => [
                    'uuid' => $h->uuid,
                    'titulo' => $h->titulo,
                    'capa_url' => $h->getAttribute('capa_url'),
                    'data_historia' => $h->data_historia ? Carbon::parse($h->data_historia)->toDateString() : null,
                ])->values()),
        ];
    }
}