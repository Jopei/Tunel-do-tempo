<?php

namespace App\DTO;

class UsuarioCreateDTO
{
    public function __construct(
        public string $nome,
        public string $email,
        public string $senha,
        public int $tipoUsuarioId,
        public ?string $aniversario = null,
        public ?string $telefone = null,
        public ?string $descricao = null,
    ) {}
}
