<?php

namespace App\Http\Controllers;

use App\Http\Requests\MusicaStoreRequest;
use App\Services\MusicaService;
use App\DTO\MusicaCreateDTO;
use Illuminate\Http\JsonResponse;
use Throwable;

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
}
