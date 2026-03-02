<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'nome' => $this->nome,
            'email' => $this->email,
            'tipo_usuario_id' => $this->tipo_usuario_id,
            'aniversario' => $this->aniversario,
            'telefone' => $this->telefone,
            'descricao' => $this->descricao,
            'created_at' => $this->created_at,
        ];
    }
}