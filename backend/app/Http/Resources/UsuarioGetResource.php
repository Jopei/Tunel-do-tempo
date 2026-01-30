<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TipoImagemEnum;

class UsuarioGetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fotoPerfil = $this->fotos
            ->where('tipo.nome', TipoImagemEnum::PERFIL->value)
            ->first();

        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'descricao' => $this->descricao,
            'aniversario' => $this->aniversario,
            'tipo_usuario' => [
                'id' => $this->tipoUsuario->id,
                'nome' => $this->tipoUsuario->nome,
            ],
            'foto' => $fotoPerfil ? [
                'uuid' => $fotoPerfil->uuid,
                'url' => config('app.url') . '/api/fotos/' . $fotoPerfil->uuid,
            ] : null,
        ];
    }
}
