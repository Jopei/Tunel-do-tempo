<?php

namespace App\Http\Resources;

use App\Mapping\MusicaMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class MusicaResource extends JsonResource
{
    /** Validade da URL assinada de stream. */
    public const VALIDADE_HORAS = 2;

    public function toArray(Request $request): array
    {
        $expira = now()->addHours(self::VALIDADE_HORAS);
        $capa = $this->capa;

        return [
            'uuid' => $this->{MusicaMapping::UUID},
            'titulo' => $this->{MusicaMapping::TITULO},
            'artista' => $this->{MusicaMapping::ARTISTA},
            'album' => $this->{MusicaMapping::ALBUM},
            'ano' => $this->{MusicaMapping::ANO},
            'duracao_segundos' => $this->{MusicaMapping::DURACAO_SEGUNDOS},
            'capa_url' => $capa && !$capa->trashed() ? route('api.fotos.show', $capa->uuid) : null,
            'url' => URL::temporarySignedRoute('api.musicas.stream', $expira, ['uuid' => $this->{MusicaMapping::UUID}]),
            'url_expira_em' => $expira->toIso8601String(),
        ];
    }
}
