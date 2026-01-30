<?php

namespace App\Repositories\Pivots;

use App\Mapping\UsuarioMusicaMapping;
use App\Models\Pivots\UsuarioMusica;

class UsuarioMusicaRepository
{
    public function insert(array $data): bool
    {
        return UsuarioMusica::insert($data);
    }

    public function delete(int $usuarioId): int
    {
        return UsuarioMusica::where(UsuarioMusicaMapping::USUARIO_ID, $usuarioId)->delete();
    }
}
