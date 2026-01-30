<?php

namespace App\Repositories;

use App\Models\Foto;
use Illuminate\Support\Str;
use App\Mapping\FotoMapping;

class FotoRepository
{
    public function create(array $data): Foto
    {
        return Foto::create([
            FotoMapping::UUID => Str::uuid()->toString(),
            FotoMapping::TITULO => $data['titulo'],
            FotoMapping::DESCRICAO => $data['descricao'],
            FotoMapping::PATH => $data['path'],
            FotoMapping::TIPO_IMAGEM_ID => $data['tipo_imagem_id'],
            FotoMapping::EXTERNAL_LINK => null,
        ]);
    }

    
    public function buscarPorUuid(string $uuid): Foto
    {
        return Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();
    }
}
