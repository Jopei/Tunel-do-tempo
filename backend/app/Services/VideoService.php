<?php

namespace App\Services;

use App\DTO\VideoCreateDTO;
use App\Mapping\VideoMapping;
use App\Models\Video;
use App\Repositories\VideoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VideoService
{
    public function __construct(
        protected VideoRepository $videoRepository
    ) {}

    public function criar(VideoCreateDTO $dto)
    {
        if (!$dto->video && !$dto->externalLink) {
            throw new InvalidArgumentException(
                'Informe um arquivo de vídeo ou um link externo.'
            );
        }

        $path = null;
        $thumbnailPath = null;

        if ($dto->video) {
            $nomeVideo = Str::uuid() . '.' . $dto->video->extension();
            $path = $dto->video->storeAs('uploads/videos', $nomeVideo);
        }

        if ($dto->thumbnail) {
            $nomeThumb = Str::uuid() . '.' . $dto->thumbnail->extension();
            $thumbnailPath = $dto->thumbnail->storeAs('uploads/videos/thumbs', $nomeThumb);
        }

        return $this->videoRepository->create([
            VideoMapping::TITULO => $dto->titulo,
            VideoMapping::DESCRICAO => $dto->descricao,
            VideoMapping::PATH => $path,
            VideoMapping::THUMBNAIL => $thumbnailPath,
            VideoMapping::EXTERNAL_LINK => $dto->externalLink,
        ]);
    }

    public function criarArquivo(
        UploadedFile $arquivo,
        string $tituloHistoria
    ): Video {
        $nomeArquivo = Str::uuid() . '.' . $arquivo->extension();

        $path = $arquivo->storeAs(
            'uploads/videos',
            $nomeArquivo
        );

        return Video::create([
            VideoMapping::UUID => Str::uuid(),
            VideoMapping::TITULO => $tituloHistoria,
            VideoMapping::DESCRICAO => 'null',
            VideoMapping::PATH => $path,
            VideoMapping::THUMBNAIL => 'null',
            VideoMapping::EXTERNAL_LINK => null,
        ]);
    }
}
