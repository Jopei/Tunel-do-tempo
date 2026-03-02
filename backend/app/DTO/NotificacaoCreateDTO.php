<?php

namespace App\DTO;

class NotificacaoCreateDTO
{
    public function __construct(
        public string $titulo,
        public ?string $descricao = null,
        public ?string $tema = null,
        public ?string $link = null
    ) {}
}