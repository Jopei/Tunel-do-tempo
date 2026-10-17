<?php

namespace App\DTO;

class VideoIndexDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?string $q = null,
        public ?int $ano = null,
        public ?string $usuarioUuid = null,
        public string $ordem = 'recentes'
    ) {}
}
