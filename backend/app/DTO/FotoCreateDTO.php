<?php

namespace App\DTO;

use Illuminate\Http\UploadedFile;

class FotoCreateDTO
{
    public function __construct(
        public UploadedFile $imagem,
        public int $tipoImagemId,
        public ?array $usuariosUuid = null,
        public ?string $titulo = null,
        public ?string $descricao = null,
    ) {}
}
