<?php

namespace App\Repositories\Pivots;

use App\Mapping\FotoMapping;
use App\Mapping\UsuarioFotoMapping;
use App\Models\Pivots\UsuarioFoto;

class UsuarioFotoRepository
{
    public function insert(array $data): bool
    {
        // Query builder nao preenche timestamps: o vinculo precisa de created_at para ordenar "mais recente".
        if (isset($data[UsuarioFotoMapping::USUARIO_ID])) {
            $data += [UsuarioFotoMapping::CREATED_AT => now(), UsuarioFotoMapping::UPDATED_AT => now()];
        }

        return UsuarioFoto::insert($data);
    }

    public function delete(int $usuarioId): int
    {
        return UsuarioFoto::where(UsuarioFotoMapping::USUARIO_ID, $usuarioId)->delete();
    }

    /** Desvincula do usuario apenas as fotos do tipo informado (ex.: Perfil), preservando as demais. */
    public function deletePorTipoImagem(int $usuarioId, int $tipoImagemId): int
    {
        return UsuarioFoto::where(UsuarioFotoMapping::USUARIO_ID, $usuarioId)
            ->whereIn(UsuarioFotoMapping::FOTO_ID, function ($q) use ($tipoImagemId) {
                $q->select(FotoMapping::ID)
                    ->from(FotoMapping::MODEL_TABLE_NAME)
                    ->where(FotoMapping::TIPO_IMAGEM_ID, $tipoImagemId);
            })
            ->delete();
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
