<?php

namespace App\Repositories;

use App\Models\Video;
use App\Mapping\VideoMapping;
use Illuminate\Support\Str;
use App\DTO\VideoIndexDTO;
use App\Enums\TipoImagemEnum;
use App\Support\Sanitizador;
use Illuminate\Support\Facades\DB;

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
            VideoMapping::DATA_VIDEO => $data[VideoMapping::DATA_VIDEO] ?? null,
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

        if (array_key_exists('data_video', $dados)) {
            $dadosFiltrados[VideoMapping::DATA_VIDEO] = $dados['data_video'];
        }

        $video->update($dadosFiltrados);

        return $video->refresh();
    }

    public function excluir(Video $video): void
    {
        $video->delete();
    }

    public function paginar(VideoIndexDTO $dto)
    {
        $query = Video::query()->with([
            'pasta:id,uuid',
            'cadastradoPor:id,uuid,nome,slug',
            'cadastradoPor.fotos' => fn ($q) => $q->whereNull('fotos.deleted_at')
                ->whereHas('tipo', fn ($t) => $t->where('nome', TipoImagemEnum::PERFIL->value)),
            'cadastradoPor.fotos.tipo:id,nome',
        ]);

        if (!$dto->todas && $dto->pastaUuid) {
            $dto->pastaUuid === 'raiz'
                ? $query->whereNull(VideoMapping::PASTA_ID)
                : $query->whereHas('pasta', fn ($q) => $q->where('video_pastas.uuid', $dto->pastaUuid));
        }

        if ($dto->q) {
            $like = Sanitizador::like($dto->q);
            $query->where(fn ($q) => $q->where(VideoMapping::TITULO, 'like', $like)
                ->orWhere(VideoMapping::DESCRICAO, 'like', $like));
        }

        if ($dto->ano) {
            $query->whereYear(VideoMapping::DATA_VIDEO, $dto->ano);
        }

        if ($dto->semData) {
            $query->whereNull(VideoMapping::DATA_VIDEO);
        }

        if ($dto->usuarioUuid) {
            $query->whereHas('usuarios', fn ($q) => $q->where('usuarios.uuid', $dto->usuarioUuid));
        }

        match ($dto->ordem) {
            'data_video' => $query->orderByRaw('data_video IS NULL')->orderBy(VideoMapping::DATA_VIDEO)->orderBy('id'),
            'data_video_desc' => $query->orderByRaw('data_video IS NULL')->orderByDesc(VideoMapping::DATA_VIDEO)->orderByDesc('id'),
            'antigas' => $query->orderBy(VideoMapping::CREATED_AT)->orderBy('id'),
            'titulo' => $query->orderBy(VideoMapping::TITULO)->orderByDesc('id'),
            'titulo_desc' => $query->orderByDesc(VideoMapping::TITULO)->orderByDesc('id'),
            'duracao' => $query->orderByRaw('duracao_segundos IS NULL')->orderBy('duracao_segundos')->orderByDesc('id'),
            'duracao_desc' => $query->orderByRaw('duracao_segundos IS NULL')->orderByDesc('duracao_segundos')->orderByDesc('id'),
            'tamanho' => $query->orderByRaw('tamanho_bytes IS NULL')->orderBy('tamanho_bytes')->orderByDesc('id'),
            'tamanho_desc' => $query->orderByRaw('tamanho_bytes IS NULL')->orderByDesc('tamanho_bytes')->orderByDesc('id'),
            default => $query->orderByDesc(VideoMapping::CREATED_AT)->orderByDesc('id'),
        };

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /** @return array<int, array{ano:?int,total:int}> */
    public function anos(?string $pastaUuid = null, bool $todas = false, ?string $q = null): array
    {
        $ano = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', data_video) AS INTEGER)"
            : 'YEAR(data_video)';

        $query = Video::query();

        if (!$todas && $pastaUuid) {
            $pastaUuid === 'raiz'
                ? $query->whereNull(VideoMapping::PASTA_ID)
                : $query->whereHas('pasta', fn ($p) => $p->where('video_pastas.uuid', $pastaUuid));
        }

        if ($q) {
            $like = Sanitizador::like($q);
            $query->where(fn ($w) => $w->where(VideoMapping::TITULO, 'like', $like)
                ->orWhere(VideoMapping::DESCRICAO, 'like', $like));
        }

        return $query
            ->selectRaw("$ano as ano, COUNT(*) as total")
            ->groupByRaw($ano)
            ->orderByRaw('ano IS NULL')
            ->orderByRaw('ano desc')
            ->get()
            ->map(fn ($l) => ['ano' => $l->ano !== null ? (int) $l->ano : null, 'total' => (int) $l->total])
            ->all();
    }
}
