<?php

namespace App\Repositories\Pivots;

use App\Mapping\UsuarioFotoMapping;
use App\Models\Pivots\UsuarioFoto;

class UsuarioFotoRepository
{
    public function insert(array $data): bool
    {
        return UsuarioFoto::insert($data);
    }

    public function delete(int $usuarioId): int
    {
        return UsuarioFoto::where(UsuarioFotoMapping::USUARIO_ID, $usuarioId)->delete();
    }

    public function listarUsuariosIdsPorFoto(int $fotoId): array
    {
        return UsuarioFoto::where(
            UsuarioFotoMapping::FOTO_ID,
            $fotoId
        )->pluck(
            UsuarioFotoMapping::USUARIO_ID
        )->toArray();
    }

    public function deleteByUsuarioAndFoto(int $usuarioId, int $fotoId): int
    {
        return UsuarioFoto::where(
            UsuarioFotoMapping::USUARIO_ID,
            $usuarioId
        )->where(
            UsuarioFotoMapping::FOTO_ID,
            $fotoId
        )->delete();
    }
}
