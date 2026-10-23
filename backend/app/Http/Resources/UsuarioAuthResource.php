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
            'telefone' => $this->telefone,
            'aniversario' => $this->aniversario,
            'descricao' => $this->descricao,
            'tipo_usuario' => $this->tipoUsuario->nome,
            // foto_perfil: caminho cru da foto ATUAL (compatibilidade); use foto_url (com ?v=).
            'foto_perfil' => $this->fotoPerfilAtual()?->path,
            'foto_url' => $this->fotoPerfilUrl(),
        ];
    }
}
