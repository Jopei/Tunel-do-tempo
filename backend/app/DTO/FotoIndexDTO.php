<?php

namespace App\DTO;

class FotoIndexDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15
    ) {}
}