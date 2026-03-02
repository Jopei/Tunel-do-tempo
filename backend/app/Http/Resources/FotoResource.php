<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Mapping\FotoMapping;

class FotoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->{FotoMapping::UUID},
            'titulo' => $this->{FotoMapping::TITULO},
            'descricao' => $this->{FotoMapping::DESCRICAO},
            'tipo_imagem_id' => $this->{FotoMapping::TIPO_IMAGEM_ID},
            'url' => route('api.fotos.show', $this->{FotoMapping::UUID}),
            'usuario_cadastrou_id' => $this->{FotoMapping::USUARIO_CADASTROU_ID},
            'created_at' => $this->{FotoMapping::CREATED_AT},
        ];
    }
}