<?php

namespace App\Http\Controllers;

use App\Actions\Videos\IndexVideoAction;
use App\Http\Requests\VideoStoreRequest;
use App\Services\VideoService;
use App\DTO\VideoCreateDTO;
use App\DTO\VideoIndexDTO;
use App\DTO\VideoUpdateDTO;
use App\Http\Requests\VideoUpdateRequest;
use App\Http\Resources\VideoResource;
use App\Mapping\VideoMapping;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\VideoIndexRequest;
use App\Repositories\VideoRepository;
use App\Enums\TipoImagemEnum;
use Throwable;

class VideoController extends Controller
{
    public function __construct(
        protected VideoService $videoService,
        protected IndexVideoAction $videoIndexAction
    ) {}

    public function store(VideoStoreRequest $request): JsonResponse
    {
        try {
            $dto = new VideoCreateDTO(
                titulo: $request->titulo,
                descricao: $request->descricao,
                video: $request->file('video'),
                externalLink: $request->external_link,
                thumbnail: $request->file('thumbnail'),
                dataVideo: $request->input('data_video'),
            );

            $video = $this->videoService->criar($dto);

            return response()->json([
                'message' => 'Vídeo criado com sucesso.',
                'uuid' => $video->uuid,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(string $uuid)
    {
        $video = Video::where(VideoMapping::UUID, $uuid)->firstOrFail();

        if ($video->external_link) {
            return redirect()->away($video->external_link);
        }

        if (!Storage::disk('public')->exists($video->path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($video->path),
            [
                'Cache-Control' => 'public, max-age=86400',
                'X-Video-UUID' => $video->uuid,
            ]
        );
    }

    public function index(VideoIndexRequest $request)
    {
        $dto = new VideoIndexDTO(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            q: $request->input('q'),
            ano: $request->filled('ano') ? (int) $request->input('ano') : null,
            usuarioUuid: $request->input('usuario_uuid'),
            ordem: $request->input('ordem', 'recentes'),
            pastaUuid: $request->input('pasta_uuid'),
            todas: $request->boolean('todas'),
            semData: $request->boolean('sem_data'),
        );

        $videos = $this->videoIndexAction->executar($dto);

        return VideoResource::collection($videos);
    }

    /** @return array<int, array{ano:?int,total:int}> total de videos por ano de data_video (null = sem data), ano desc, null por ultimo. */
    public function anos(VideoIndexRequest $request, VideoRepository $videos): JsonResponse
    {
        return response()->json($videos->anos(
            $request->input('pasta_uuid'),
            $request->boolean('todas'),
            $request->input('q'),
        ));
    }

    /** Mesmo item da listagem, em JSON (o show devolve o arquivo/redirect). */
    public function detalhes(string $uuid)
    {
        $video = Video::where(VideoMapping::UUID, $uuid)->with(['pasta:id,uuid', 'cadastradoPor:id,uuid,nome,slug', 'cadastradoPor.fotos' => fn ($q) => $q->whereNull('fotos.deleted_at')->whereHas('tipo', fn ($t) => $t->where('nome', TipoImagemEnum::PERFIL->value)), 'cadastradoPor.fotos.tipo:id,nome'])->firstOrFail();

        return new VideoResource($video);
    }

    public function update(VideoUpdateRequest $request, string $uuid)
    {
        $dto = new VideoUpdateDTO(
            uuid: $uuid,
            titulo: $request->titulo,
            descricao: $request->descricao,
            dataVideo: $request->input('data_video'),
            informouDataVideo: $request->exists('data_video'),
        );

        $this->videoService->editar($dto);

        return response()->json([
            'message' => 'Vídeo atualizado com sucesso.'
        ]);
    }

    public function destroy(string $uuid)
    {
        $this->videoService->remover($uuid);

        return response()->json([
            'message' => 'Vídeo removido com sucesso.'
        ]);
    }
}
