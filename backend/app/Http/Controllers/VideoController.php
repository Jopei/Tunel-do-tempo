<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoStoreRequest;
use App\Services\VideoService;
use App\DTO\VideoCreateDTO;
use Illuminate\Http\JsonResponse;
use Throwable;

class VideoController extends Controller
{
    public function __construct(
        protected VideoService $videoService
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
}
