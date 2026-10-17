<?php

namespace App\Http\Resources;

use App\Enums\TipoUsuarioEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LembrancaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $logado = $request->user();

        return [
            'uuid' => $this->uuid,
            'texto' => $this->texto,
            'contexto' => $this->contexto,
            'created_at' => $this->created_at,
            'usuario' => $this->usuario ? [
                'uuid' => $this->usuario->uuid,
                'nome' => $this->usuario->nome,
            ] : null,
            'pode_excluir' => $logado
                ? ($logado->id === $this->usuario_id
                    || $logado->tipoUsuario?->nome === TipoUsuarioEnum::MEMBRO_ADM->value)
                : false,
        ];
    }
}
