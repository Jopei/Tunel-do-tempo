<?php

namespace App\Http\Controllers;

use App\Http\Requests\MusicaStoreRequest;
use App\Services\MusicaService;
use App\DTO\MusicaCreateDTO;
use App\Mapping\MusicaMapping;
use App\Models\Musica;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\Storage;

class MusicaController extends Controller
{
    public function __construct(
        protected MusicaService $musicaService
    ) {}

    public function store(MusicaStoreRequest $request): JsonResponse
    {
        try {
            $dto = new MusicaCreateDTO(
                titulo: $request->titulo,
                musica: $request->file('musica'),
            );

            $musica = $this->musicaService->criar($dto);

            return response()->json([
                'message' => 'Música criada com sucesso.',
                'uuid' => $musica->uuid,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar música.',
            ], 500);
        }
    }

    public function show(string $uuid)
    {
        $musica = Musica::where(MusicaMapping::UUID, $uuid)->firstOrFail();

        if (!Storage::disk('public')->exists($musica->path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($musica->path),
            [
                'Cache-Control' => 'public, max-age=86400',
                'X-Musica-UUID' => $musica->uuid,
            ]
        );
    }
}
