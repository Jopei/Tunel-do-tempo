<?php

namespace App\Services;

use App\DTO\UsuarioCreateDTO;
use App\Repositories\UsuarioRepository;
use App\Models\Usuario;

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

    
}
