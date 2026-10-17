<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriaShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao_curta' => $this->descricao_curta,
            'conteudo' => $this->conteudo,
            'data_historia' => $this->data_historia,
            'tipo_historia' => $this->tipo?->nome,

            'usuarios' => $this->usuarios->map(fn($u) => [
                'uuid' => $u->uuid,
                'nome' => $u->nome,
            ]),

            // Compatibilidade: arrays de uuid (formato original)
            'fotos' => $this->fotos->pluck('uuid'),
            'videos' => $this->videos->pluck('uuid'),
            'musicas' => $this->musicas->pluck('uuid'),

            // Midia com URL pronta
            'fotos_midia' => $this->fotos->map(fn($f) => [
                'uuid' => $f->uuid,
                'url' => route('api.fotos.show', $f->uuid),
                'titulo' => $f->titulo,
                'descricao' => $f->descricao,
            ])->values(),
            'videos_midia' => $this->videos->map(fn($v) => [
                'uuid' => $v->uuid,
                'url' => route('api.videos.show', $v->uuid),
                'poster_url' => $v->thumbnail ? asset('storage/' . $v->thumbnail) : null,
                'titulo' => $v->titulo,
            ])->values(),
            'musicas_midia' => $this->musicas->map(fn($m) => [
                'uuid' => $m->uuid,
                'url' => route('api.musicas.show', $m->uuid),
                'titulo' => $m->titulo,
            ])->values(),

            'lembrancas_count' => (int) ($this->lembrancas_count ?? 0),
            'favoritado' => (bool) ($this->favoritado ?? false),
        ];
    }
}
