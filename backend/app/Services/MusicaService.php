<?php

namespace App\Services;

use App\DTO\MusicaCreateDTO;
use App\Mapping\MusicaMapping;
use App\Models\Musica;
use App\Repositories\MusicaRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MusicaService
{
    public function __construct(
        protected MusicaRepository $musicaRepository
    ) {}

    public function criar(MusicaCreateDTO $dto)
    {
        $nomeArquivo = Str::uuid() . '.' . $dto->musica->extension();

        $path = $dto->musica->storeAs(
            'uploads/musicas',
            $nomeArquivo
        );

        return $this->musicaRepository->create([
            MusicaMapping::TITULO => $dto->titulo,
            MusicaMapping::PATH => $path,
        ]);
    }

    public function criarArquivo(
        UploadedFile $arquivo,
        string $tituloHistoria
    ): Musica {
        $nomeArquivo = Str::uuid() . '.' . $arquivo->extension();

        $path = $arquivo->storeAs(
            'uploads/musicas',
            $nomeArquivo
        );

        return Musica::create([
            MusicaMapping::UUID => Str::uuid(),
            MusicaMapping::TITULO => $tituloHistoria,
            MusicaMapping::PATH => $path,
        ]);
    }
}
