<?php

namespace App\Actions\Videos;

use App\Repositories\VideoRepository;
use App\DTO\VideoIndexDTO;

class IndexVideoAction
{
    public function __construct(
        protected VideoRepository $videoRepository
    ) {}

    public function executar(VideoIndexDTO $dto)
    {
        return $this->videoRepository->paginar($dto->perPage);
    }
}