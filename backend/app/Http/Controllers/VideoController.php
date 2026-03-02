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
use Symfony\Component\HttpFoundation\Request;
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
            );

            $video = $this->videoService->criar($dto);

            return response()->json([
                'message' => 'Vídeo criado com sucesso.',
                'uuid' => $video->uuid,
            ], 201);
        } catch (Throwable $e) {
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

    public function index(Request $request)
    {
        $dto = new VideoIndexDTO(
            page: $request->page ?? 1,
            perPage: $request->per_page ?? 15
        );

        $videos = $this->videoIndexAction->executar($dto);

        return VideoResource::collection($videos);
    }

    public function update(VideoUpdateRequest $request, string $uuid)
    {
        $dto = new VideoUpdateDTO(
            uuid: $uuid,
            titulo: $request->titulo,
            descricao: $request->descricao
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
