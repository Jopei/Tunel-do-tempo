<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class MusicaCreateDTO
{
    public function __construct(
        public string $titulo,
        public UploadedFile $musica,
        public ?string $artista = null,
        public ?string $album = null,
        public ?int $ano = null,
        public ?int $duracaoSegundos = null,
        public ?UploadedFile $capa = null,
        public ?string $capaFotoUuid = null,
    ) {}
}
