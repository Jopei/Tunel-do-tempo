<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoMoverPastaRequest;
use App\Http\Requests\VideoPastaStoreRequest;
use App\Http\Requests\VideoPastaUpdateRequest;
use App\Http\Resources\VideoPastaResource;
use App\Services\VideoPastaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoPastaController extends Controller
{
    public function __construct(protected VideoPastaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['pai_uuid' => ['nullable', 'uuid']]);

        $r = $this->service->listar($request->input('pai_uuid'));

        return response()->json([
            'pasta_atual' => $r['pasta_atual'] ? ['uuid' => $r['pasta_atual']->uuid, 'nome' => $r['pasta_atual']->nome] : null,
            'caminho' => $r['caminho'],
            'pastas' => VideoPastaResource::collection($r['pastas'])->resolve($request),
        ]);
    }

    public function store(VideoPastaStoreRequest $request): JsonResponse
    {
        $pasta = $this->service->criar($request->input('nome'), $request->input('pai_uuid'));

        return (new VideoPastaResource($pasta->load('pai:id,uuid')))->response()->setStatusCode(201);
    }

    public function update(VideoPastaUpdateRequest $request, string $uuid): VideoPastaResource
    {
        $pasta = $this->service->atualizar($uuid, $request->validated(), $request->exists('pai_uuid'));

        return new VideoPastaResource($pasta->load('pai:id,uuid'));
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->excluir($uuid);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['message' => 'Pasta removida com sucesso.']);
    }

    public function moverVideo(VideoMoverPastaRequest $request, string $uuid): JsonResponse
    {
        $pasta = $this->service->moverVideo($uuid, $request->input('pasta_uuid'));

        return response()->json([
            'message' => 'Vídeo movido com sucesso.',
            'pasta_uuid' => $pasta?->uuid,
        ]);
    }
}
