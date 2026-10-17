<?php

namespace App\Repositories;

use App\DTO\UsuarioCreateDTO;
use App\Enums\TipoUsuarioEnum;
use App\Models\Usuario;
use Illuminate\Support\Str;
use App\Mapping\UsuarioMapping;
use App\Mapping\TipoUsuarioMapping;
use App\Models\TipoUsuario;

class UsuarioRepository
{
    public function create(UsuarioCreateDTO $dto): Usuario
    {
        return Usuario::create([
            UsuarioMapping::UUID => Str::uuid()->toString(),
            UsuarioMapping::NOME => $dto->nome,
            UsuarioMapping::EMAIL => $dto->email,
            UsuarioMapping::SENHA => $dto->senha,
            UsuarioMapping::TIPO_USUARIO_ID => $dto->tipoUsuarioId,
            UsuarioMapping::ANIVERSARIO => $dto->aniversario,
            UsuarioMapping::TELEFONE => $dto->telefone,
            UsuarioMapping::DESCRICAO => $dto->descricao,
        ]);
    }

    public function listarUsuariosPermitidos()
    {
        $tiposPermitidos = TipoUsuario::whereIn(
            TipoUsuarioMapping::NOME,
            [
                TipoUsuarioEnum::MEMBRO_ADM->value,
                TipoUsuarioEnum::MEMBRO_RUA->value,
            ]
        )->pluck(TipoUsuarioMapping::ID);

        return Usuario::with(['fotos' => fn ($q) => $q->whereNull('fotos.deleted_at'), 'fotos.tipo'])
            ->whereIn(
            UsuarioMapping::TIPO_USUARIO_ID,
            $tiposPermitidos
        )
            ->orderBy(UsuarioMapping::NOME)
            ->get();
    }

    public function buscarUsuarioCompletoPorUuid(string $uuid): Usuario
    {
        return Usuario::with([
            'tipoUsuario',
            'fotos.tipo'
        ])
            ->where(UsuarioMapping::UUID, $uuid)
            ->firstOrFail();
    }

    public function buscarPorUuid(string $uuid): Usuario
    {
        return Usuario::where('uuid', $uuid)->firstOrFail();
    }

    public function buscarIdsPorUuids(array $uuids): array
    {
        return Usuario::whereIn('uuid', $uuids)->pluck('id')->toArray();
    }

    public function atualizarPorUuid(string $uuid, array $dados)
    {
        return Usuario::where(UsuarioMapping::UUID, $uuid)
            ->update($dados);
    }

    /**
     * Membros (ADM e RUA) com a foto de perfil, para exibicao publica ou busca.
     */
    public function listarMembros(?string $termo = null, ?int $limite = null)
    {
        $tiposPermitidos = TipoUsuario::whereIn(
            TipoUsuarioMapping::NOME,
            [
                TipoUsuarioEnum::MEMBRO_ADM->value,
                TipoUsuarioEnum::MEMBRO_RUA->value,
            ]
        )->pluck(TipoUsuarioMapping::ID);

        return Usuario::query()
            ->whereIn(UsuarioMapping::TIPO_USUARIO_ID, $tiposPermitidos)
            ->when($termo, fn ($q) => $q->where(UsuarioMapping::NOME, 'like', \App\Support\Sanitizador::like($termo)))
            ->with(['fotos' => fn ($q) => $q->whereNull('fotos.deleted_at'), 'fotos.tipo'])
            ->orderBy(UsuarioMapping::NOME)
            ->when($limite, fn ($q) => $q->limit($limite))
            ->get();
    }
}
