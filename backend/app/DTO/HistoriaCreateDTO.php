<?php

namespace App\DTO;

class HistoriaCreateDTO
{
    public function __construct(
        public string $titulo,
        public string $descricaoCurta,
        public string $conteudo,
        public string $dataHistoria,
        public string $tipoHistoriaId,
        public array $usuarios = [],
        public array $fotos = [],
        public array $videos = [],
        public array $musicas = [],
    ) {}
}
