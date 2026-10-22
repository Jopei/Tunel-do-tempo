<?php

namespace App\DTO;

class FotoUpdateDTO
{
    public function __construct(
        public string $uuid,
        public ?string $titulo = null,
        public ?string $descricao = null,
        public ?string $dataFoto = null,
        public bool $informouDataFoto = false,
    ) {}
}