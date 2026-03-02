<?php

namespace App\DTO;

class VideoUpdateDTO
{
    public function __construct(
        public string $uuid,
        public ?string $titulo = null,
        public ?string $descricao = null,
    ) {}
}