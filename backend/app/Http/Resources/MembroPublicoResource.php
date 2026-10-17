<?php

namespace App\Http\Resources;

use App\Enums\TipoImagemEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dados minimos e publicos de um membro (sem e-mail/telefone).
 */
class MembroPublicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'foto_url' => self::fotoUrl($this->resource),
        ];
    }

    /** URL da foto de perfil mais recente (requer fotos.tipo carregado) ou null. */
    public static function fotoUrl($usuario): ?string
    {
        $foto = $usuario->fotos
            ->filter(fn ($f) => $f->tipo?->nome === TipoImagemEnum::PERFIL->value)
            ->sortByDesc(fn ($f) => $f->pivot?->created_at ?? $f->created_at)
            ->first();

        return $foto ? route('api.fotos.show', $foto->uuid) : null;
    }
}
