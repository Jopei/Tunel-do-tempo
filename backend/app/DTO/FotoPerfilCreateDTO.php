<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class FotoPerfilCreateDTO
{
    public function __construct(
        public UploadedFile $imagem,
        public string $usuarioUuid,
        public ?string $titulo = null,
        public ?string $descricao = null,
    ) {}
}
