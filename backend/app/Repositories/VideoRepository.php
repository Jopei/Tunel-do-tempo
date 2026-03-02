<?php

namespace App\Repositories;

use App\Models\Video;
use App\Mapping\VideoMapping;
use Illuminate\Support\Str;

class VideoRepository
{
    public function criar(array $data): Video
    {
        $data[VideoMapping::UUID] = Str::uuid();

        return Video::create($data);
    }

    public function create(array $data): Video
    {
        return Video::create([
            VideoMapping::UUID => Str::uuid(),
            VideoMapping::TITULO => $data[VideoMapping::TITULO],
            VideoMapping::DESCRICAO => $data[VideoMapping::DESCRICAO],
            VideoMapping::PATH => $data[VideoMapping::PATH],
            VideoMapping::THUMBNAIL => $data[VideoMapping::THUMBNAIL],
            VideoMapping::EXTERNAL_LINK => $data[VideoMapping::EXTERNAL_LINK],
            VideoMapping::USUARIO_CADASTROU_ID => $data[VideoMapping::USUARIO_CADASTROU_ID],
        ]);
    }

    public function buscarPorUuid(string $uuid): Video
    {
        return Video::where(VideoMapping::UUID, $uuid)->firstOrFail();
    }

    public function atualizar(Video $video, array $dados): Video
    {
        $dadosFiltrados = array_filter([
            VideoMapping::TITULO => $dados['titulo'] ?? null,
            VideoMapping::DESCRICAO => $dados['descricao'] ?? null,
        ], fn($valor) => !is_null($valor));

        $video->update($dadosFiltrados);

        return $video->refresh();
    }

    public function excluir(Video $video): void
    {
        $video->delete();
    }

    public function paginar(int $perPage)
    {
        return Video::query()
            ->latest()
            ->paginate($perPage);
    }
}
