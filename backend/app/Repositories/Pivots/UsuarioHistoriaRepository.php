<?php

namespace App\Repositories\Pivots;

use App\Models\Pivots\UsuarioHistoria;
use App\Mapping\UsuarioHistoriaMapping;

class UsuarioHistoriaRepository
{
    public function insert(array $data): bool
    {
        return UsuarioHistoria::insert($data);
    }

    public function deleteByHistoria(int $historiaId): int
    {
        return UsuarioHistoria::where(
            UsuarioHistoriaMapping::HISTORIA_ID,
            $historiaId
        )->delete();
    }
}
