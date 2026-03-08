<?php

namespace App\DTO\Usuario;

class AtualizarUsuarioDTO
{
    public function __construct(
        public string $uuid,
        public string $nome,
        public string $email,
        public ?string $senha,
        public ?string $aniversario,
        public ?string $telefone,
        public ?string $descricao
    ) {}
}