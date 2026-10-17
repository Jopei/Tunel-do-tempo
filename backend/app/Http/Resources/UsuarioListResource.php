<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ehAdm = $request->user()?->tipoUsuario?->nome === \App\Enums\TipoUsuarioEnum::MEMBRO_ADM->value;

        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'aniversario' => $this->aniversario,
            'descricao' => $this->descricao,
            'foto_url' => MembroPublicoResource::fotoUrl($this->resource),
        ] + ($ehAdm ? ['email' => $this->email, 'telefone' => $this->telefone] : []);
    }
}
