<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class MusicaCreateDTO
{
    public function __construct(
        public string $titulo,
        public UploadedFile $musica,
    ) {}
}
