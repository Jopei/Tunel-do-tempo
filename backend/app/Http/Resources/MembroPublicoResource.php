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
            'slug' => $this->slug,
            'foto_url' => self::fotoUrl($this->resource),
        ];
    }

    /** URL da foto de perfil atual (requer fotos.tipo carregado) ou null. */
    public static function fotoUrl($usuario): ?string
    {
        return $usuario?->fotoPerfilUrl();
    }
}
