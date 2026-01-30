<?php

namespace App\Repositories\Pivots;

use App\Mapping\UsuarioVideoMapping;
use App\Models\Pivots\UsuarioVideo;

class UsuarioVideoRepository
{
    public function insert(array $data): bool
    {
        return UsuarioVideo::insert($data);
    }

    public function delete(int $usuarioId): int
    {
        return UsuarioVideo::where(UsuarioVideoMapping::USUARIO_ID, $usuarioId)->delete();
    }
}
