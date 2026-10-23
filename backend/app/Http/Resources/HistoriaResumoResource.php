<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriaResumoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao_curta' => $this->descricao_curta,
            'tipo_historia' => $this->tipo?->nome,
            'data_historia' => $this->data_historia
                ? Carbon::parse($this->data_historia)->toDateString()
                : null,
            'fotos_count' => $this->contagem('fotos'),
            'videos_count' => $this->contagem('videos'),
            'musicas_count' => $this->contagem('musicas'),

            'capa_url' => self::capaUrl($this->resource),
            'favoritos_count' => (int) ($this->favoritos_count ?? 0),
            'lembrancas_count' => (int) ($this->lembrancas_count ?? 0),
            'favoritado' => (bool) ($this->favoritado ?? false),

            'usuarios' => $this->usuarios->map(fn ($usuario) => [
                'uuid' => $usuario->uuid,
                'nome' => $usuario->nome,
                'slug' => $usuario->slug,
                'foto_url' => MembroPublicoResource::fotoUrl($usuario),
            ]),
        ];
    }

    /** Capa a partir dos atributos calculados no repositorio (miniatura preferida). */
    public static function capaUrl($historia): ?string
    {
        $attrs = $historia->getAttributes();
        if (!empty($attrs['capa_thumbnail_path'])) {
            return asset('storage/' . $attrs['capa_thumbnail_path']);
        }

        return !empty($attrs['capa_foto_uuid']) ? route('api.fotos.show', $attrs['capa_foto_uuid']) : null;
    }

    private function contagem(string $relacao): int
    {
        $atributo = $relacao . '_count';

        if (array_key_exists($atributo, $this->resource->getAttributes())) {
            return (int) $this->resource->getAttribute($atributo);
        }

        return $this->resource->{$relacao}()->whereNull($relacao . '.deleted_at')->count();
    }
}
