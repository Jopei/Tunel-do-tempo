<?php

namespace App\Services;

use App\DTO\UsuarioCreateDTO;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Access\AuthorizationException;
use App\DTO\Usuario\AtualizarUsuarioDTO;
use App\Models\Usuario;
use Illuminate\Support\Str;
use App\Models\Foto;
use App\Models\Pivots\UsuarioFoto;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\FotoMapping;

class UsuarioService
{
    public function __construct(
        protected UsuarioRepository $usuarioRepository
    ) {}

    public function criarUsuario(UsuarioCreateDTO $dto): Usuario
    {
        return $this->usuarioRepository->create($dto);
    }

    public function listarUsuarios()
    {
        return $this->usuarioRepository->listarUsuariosPermitidos();
    }

    public function buscarUsuarioPorUuid(string $uuid): Usuario
    {
        return $this->usuarioRepository->buscarPorUuid($uuid);
    }

    public function getUsuarioCompletoPorUuid(string $uuid): Usuario
    {
        return $this->usuarioRepository->buscarUsuarioCompletoPorUuid($uuid);
    }

    public function atualizar(AtualizarUsuarioDTO $dto, $usuarioLogado)
    {
        if ($usuarioLogado->uuid !== $dto->uuid) {
            throw new AuthorizationException('Você não pode editar outro usuário.');
        }

        $dadosAntigos = $this->usuarioRepository->buscarPorUuid($dto->uuid);


        $dados = [
            'nome' => $dto->nome ?? $dadosAntigos->nome,
            'email' => $dto->email ?? $dadosAntigos->email,
            'aniversario' => $dto->aniversario ?? $dadosAntigos->aniversario,
            'telefone' => $dto->telefone ?? $dadosAntigos->telefone,
            'descricao' => $dto->descricao ?? $dadosAntigos->descricao,
        ];

        if ($dto->senha) {
            $dados['senha'] = Hash::make($dto->senha);
        }

        $this->usuarioRepository->atualizarPorUuid($dto->uuid, $dados);

        return $this->usuarioRepository->buscarPorUuid($dto->uuid);
    }

    public function atualizarFotoPerfil(string $uuid, $arquivo, $usuarioLogado)
    {
        if ($usuarioLogado->uuid !== $uuid) {
            throw new AuthorizationException(
                'Você não pode alterar a foto de outro usuário.'
            );
        }

        $usuario = $this->usuarioRepository->buscarPorUuid($uuid);

        $nomeArquivo = Str::uuid() . '.' . $arquivo->getClientOriginalExtension();

        $caminho = $arquivo->storeAs(
            'usuarios/fotos',
            $nomeArquivo,
            'public'
        );

        $foto = Foto::create([
            FotoMapping::UUID => Str::uuid(),
            FotoMapping::PATH => $caminho,
            FotoMapping::TITULO => 'Foto de perfil',
        ]);

        UsuarioFoto::where(
            UsuarioFotoMapping::USUARIO_ID,
            $usuario->id
        )->update([
            UsuarioFotoMapping::DELETED_AT => now()
        ]);

        UsuarioFoto::create([
            UsuarioFotoMapping::USUARIO_ID => $usuario->id,
            UsuarioFotoMapping::FOTO_ID => $foto->id,
        ]);

        return $foto;
    }
}
