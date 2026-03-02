<?php

namespace App\Actions\Fotos;

use App\DTO\FotoIndexDTO;
use App\Repositories\FotoRepository;

class IndexFotoAction
{
    public function __construct(
        protected FotoRepository $fotoRepository
    ) {}

    public function executar(FotoIndexDTO $dto)
    {
        return $this->fotoRepository->paginar(
            $dto->perPage
        );
    }
}
