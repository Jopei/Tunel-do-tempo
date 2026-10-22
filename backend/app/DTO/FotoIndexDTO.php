<?php

namespace App\DTO;

class FotoIndexDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?string $q = null,
        public ?int $ano = null,
        public ?string $usuarioUuid = null,
        public string $ordem = 'recentes',
        public ?string $agrupar = null,
        public ?string $albumUuid = null,
        public ?string $de = null,
        public ?string $ate = null
    ) {}
}
