<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TipoImagemEnum;

class UsuarioGetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fotoPerfil = $this->fotoPerfilAtual();

        $logado = $request->user();
        $veSensivel = $logado && (
            $logado->id === $this->id
            || $logado->tipoUsuario?->nome === \App\Enums\TipoUsuarioEnum::MEMBRO_ADM->value
        );

        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            ...($veSensivel ? ['email' => $this->email, 'telefone' => $this->telefone] : []),
            'descricao' => $this->descricao,
            'aniversario' => $this->aniversario,
            'tipo_usuario' => [
                'id' => $this->tipoUsuario->id,
                'nome' => $this->tipoUsuario->nome,
            ],
            'foto' => $fotoPerfil ? [
                'uuid' => $fotoPerfil->uuid,
                'url' => $this->fotoPerfilUrl(),
            ] : null,
        ];
    }
}
