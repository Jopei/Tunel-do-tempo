<?php

namespace App\DTO\Atualizacoes;

class AtualizacaoDTO
{
    public function __construct(
        public string $titulo,
        public string $conteudoMarkdown,
        public ?string $linkMusica,
        public int $usuarioId
    ) {}
}