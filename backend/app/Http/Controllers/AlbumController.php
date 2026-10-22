<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlbumFotosRequest;
use App\Http\Requests\AlbumStoreRequest;
use App\Http\Requests\AlbumUpdateRequest;
use App\Http\Resources\AlbumResource;
use App\Services\AlbumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function __construct(protected AlbumService $service) {}

    public function index(Request $request)
    {
        $dados = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return AlbumResource::collection($this->service->listar(
            $dados['q'] ?? null,
            (int) ($dados['page'] ?? 1),
            (int) ($dados['per_page'] ?? 24),
        ));
    }

    public function show(string $uuid): AlbumResource
    {
        return new AlbumResource($this->service->buscar($uuid));
    }

    public function store(AlbumStoreRequest $request): JsonResponse
    {
        $album = $this->service->criar($request->validated());

        return (new AlbumResource($album))->response()->setStatusCode(201);
    }

    public function update(AlbumUpdateRequest $request, string $uuid): AlbumResource
    {
        return new AlbumResource($this->service->atualizar(
            $uuid,
            $request->validated(),
            $request->exists('capa_foto_uuid'),
        ));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->service->excluir($uuid);

        return response()->json(['message' => 'Álbum removido com sucesso.']);
    }

    public function adicionarFotos(AlbumFotosRequest $request, string $uuid): AlbumResource
    {
        return new AlbumResource($this->service->adicionarFotos($uuid, $request->input('fotos')));
    }

    public function removerFotos(AlbumFotosRequest $request, string $uuid): AlbumResource
    {
        return new AlbumResource($this->service->removerFotos($uuid, $request->input('fotos')));
    }
}
