<?php

namespace App\Http\Controllers;

use App\Services\Atualizacoes\AtualizacaoService;
use App\Repositories\Atualizacoes\AtualizacaoRepositoryInterface;
use App\Http\Requests\Atualizacoes\AtualizacaoStoreRequest;
use App\Http\Requests\Atualizacoes\AtualizacaoUpdateRequest;
use App\Http\Resources\AtualizacaoResource;
use App\DTO\Atualizacoes\AtualizacaoDTO;
use Illuminate\Http\JsonResponse;

class AtualizacaoController extends Controller
{
    public function __construct(
        private AtualizacaoService $service,
        private AtualizacaoRepositoryInterface $repository
    ) {}

    public function index(): JsonResponse
    {
        $atualizacoes = $this->repository->listar();

        return response()->json(
            AtualizacaoResource::collection($atualizacoes)
        );
    }

    public function store(AtualizacaoStoreRequest $request): JsonResponse
    {
        $dto = new AtualizacaoDTO(
            titulo: $request->titulo,
            conteudoMarkdown: $request->conteudo_markdown,
            linkMusica: $request->link_musica,
            usuarioId: auth()->user()->id
        );

        $atualizacao = $this->service->criar($dto);

        return response()->json(
            new AtualizacaoResource($atualizacao),
            201
        );
    }

    public function update(
        AtualizacaoUpdateRequest $request,
        string $uuid
    ): JsonResponse {

        $atualizacao = $this->repository->buscarPorUuid($uuid);

        if (!$atualizacao) {
            return response()->json(['message' => 'Atualização não encontrada'], 404);
        }

        $dto = new AtualizacaoDTO(
            titulo: $request->titulo,
            conteudoMarkdown: $request->conteudo_markdown,
            linkMusica: $request->link_musica,
            usuarioId: auth()->user()->id
        );

        $atualizacao = $this->service->atualizar($atualizacao, $dto);

        return response()->json(
            new AtualizacaoResource($atualizacao)
        );
    }
}