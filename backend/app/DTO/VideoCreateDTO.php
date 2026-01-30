<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class VideoCreateDTO
{
    public function __construct(
        public string $titulo,
        public ?string $descricao,
        public ?UploadedFile $video,
        public ?string $externalLink,
        public ?UploadedFile $thumbnail,
    ) {}
}
