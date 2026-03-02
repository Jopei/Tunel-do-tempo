<?php

namespace App\Http\Resources;

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

            'usuarios' => $this->usuarios->map(fn ($usuario) => [
                'uuid' => $usuario->uuid,
                'nome' => $usuario->nome,
            ]),
        ];
    }
}
