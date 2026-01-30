<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TipoImagemEnum;

class UsuarioAuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'email' => $this->email,
            'tipo_usuario' => $this->tipoUsuario->nome,
            'foto_perfil' => $this->fotos
                ->where('tipo.nome', TipoImagemEnum::PERFIL->value)
                ->first()?->path,
        ];
    }
}
