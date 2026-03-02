<?php

namespace App\Services;

use App\DTO\VideoCreateDTO;
use App\DTO\VideoUpdateDTO;
use App\Mapping\VideoMapping;
use App\Models\Video;
use App\Repositories\VideoRepository;
use DomainException;
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
        $usuarioId = auth()->user()->id;

        if (!$dto->video && !$dto->externalLink) {
            throw new InvalidArgumentException(
                'Informe um arquivo de vídeo ou um link externo.'
            );
        }

        $path = null;
        $thumbnailPath = null;

        if ($dto->video) {
            $nomeVideo = Str::uuid() . '.' . $dto->video->extension();
            $path = $dto->video->storeAs('uploads/videos', $nomeVideo, 'public');
        }

        if ($dto->thumbnail) {
            $nomeThumb = Str::uuid() . '.' . $dto->thumbnail->extension();
            $thumbnailPath = $dto->thumbnail->storeAs('uploads/videos/thumbs', $nomeThumb, 'public');
        }

        return $this->videoRepository->create([
            VideoMapping::TITULO => $dto->titulo,
            VideoMapping::DESCRICAO => $dto->descricao,
            VideoMapping::PATH => $path,
            VideoMapping::THUMBNAIL => $thumbnailPath,
            VideoMapping::EXTERNAL_LINK => $dto->externalLink ?? 0,
            VideoMapping::USUARIO_CADASTROU_ID => $usuarioId,
        ]);
    }

    public function criarArquivo(
        UploadedFile $arquivo,
        string $tituloHistoria
    ): Video {
        $nomeArquivo = Str::uuid() . '.' . $arquivo->extension();

        $path = $arquivo->storeAs(
            'uploads/videos',
            $nomeArquivo,
            'public'
        );

        return Video::create([
            VideoMapping::UUID => Str::uuid(),
            VideoMapping::TITULO => $tituloHistoria,
            VideoMapping::DESCRICAO => 'null',
            VideoMapping::PATH => $path,
            VideoMapping::THUMBNAIL => 'null',
            VideoMapping::EXTERNAL_LINK => 0,
        ]);
    }

    public function editar(VideoUpdateDTO $dto)
    {
        $video = $this->videoRepository->buscarPorUuid($dto->uuid);

        return $this->videoRepository->atualizar($video, [
            'titulo' => $dto->titulo,
            'descricao' => $dto->descricao,
        ]);
    }

    public function remover(string $uuid): void
    {
        $video = $this->videoRepository->buscarPorUuid($uuid);

        if ($video->trashed()) {
            throw new DomainException('Vídeo já removido.');
        }

        $this->videoRepository->excluir($video);
    }
}
