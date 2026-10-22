<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistoriaPorLimiteRequest;
use App\Services\HistoriaService;
use Exception;
use App\Http\Requests\HistoriaResumoIndexRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Requests\HistoriaStoreRequest;
use App\DTO\HistoriaCreateDTO;
use App\DTO\HistoriaUpdateDTO;
use App\Http\Requests\HistoriaUpdateRequest;
use App\Http\Resources\HistoriaResumoResource;
use App\Http\Resources\HistoriaShowResource;
use App\Repositories\TipoHistoriaRepository;
use App\Services\FotoService;
use App\Services\VideoService;
use App\Services\MusicaService;
use App\Repositories\UsuarioRepository;

class HistoriaController extends Controller
{
    protected HistoriaService $historiaService;

    public function __construct(HistoriaService $historiaService)
    {
        $this->historiaService = $historiaService;
    }

    public function buscarHistoriasPorLimite(HistoriaPorLimiteRequest $request): JsonResponse
    {
        try {
            $limite = (int) $request->query('limite', 7);
            $historias = $this->historiaService->buscarHistoriasPorLimite($limite);
            return response()->json($historias, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json('Erro ao buscar histórias', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(
        HistoriaStoreRequest $request,
        HistoriaService $historiaService,
        FotoService $fotoService,
        VideoService $videoService,
        MusicaService $musicaService,
        UsuarioRepository $usuarioRepository,
        TipoHistoriaRepository $tipoHistoriaRepository
    ) {
        try {
            $dto = new HistoriaCreateDTO(
                titulo: $request->titulo,
                descricaoCurta: $request->descricao_curta,
                conteudo: $request->conteudo,
                dataHistoria: $request->data_historia,
                tipoHistoriaId: $request->tipo_historia,
                usuarios: $request->usuarios ?? [],
                fotos: $request->file('fotos', []),
                videos: $request->file('videos', []),
                musicas: $request->file('musicas', [])
            );

            $historia = $historiaService->criarHistoria(
                $dto,
                $fotoService,
                $videoService,
                $musicaService,
                $usuarioRepository,
                $tipoHistoriaRepository
            );

            return response()->json([
                'message' => 'História criada com sucesso',
                'uuid' => $historia->uuid,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar história' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resumo(string $uuid)
    {
        try {
            $historia = $this->historiaService->buscarPorUuidComUsuarios($uuid, auth()->user()?->id);
            return response()->json(
                new HistoriaResumoResource($historia)
            );
        } catch (Exception $e) {
            return response()->json('Erro ao buscar resumo da história', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $uuid)
    {
        try {
            $historia = $this->historiaService->buscarCompletaPorUuid($uuid, auth()->user()->id);

            return response()->json(
                new HistoriaShowResource($historia)
            );
        } catch (Exception $e) {
            return response()->json($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listarResumo(HistoriaResumoIndexRequest $request)
    {
        try {
            $filtros = $request->only(['q', 'tipo', 'usuario', 'ordem']);

            // Sem page/per_page: lista completa (formato original). Com eles: paginado com meta.
            $perPage = ($request->has('page') || $request->has('per_page'))
                ? (int) $request->input('per_page', 15)
                : null;

            $historias = $this->historiaService->listarResumo($filtros, $perPage, auth()->user()?->id);

            if ($perPage) {
                return HistoriaResumoResource::collection($historias);
            }

            return response()->json(
                HistoriaResumoResource::collection($historias)
            );
        } catch (Exception $e) {
            return response()->json(
                'Erro ao listar resumos das histórias',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function update(string $uuid, HistoriaUpdateRequest $request)
    {
        $historia = $this->historiaService->buscarPorUuid($uuid);

        if (Gate::denies('update', $historia)) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $dto = new HistoriaUpdateDTO($request->validated());

            $historia = $this->historiaService->atualizarHistoria($uuid, $dto);

            return response()->json(
                new HistoriaResumoResource($historia)
            );
        } catch (Exception $e) {
            return response()->json(
                'Erro ao atualizar história',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(string $uuid)
    {
        $historia = $this->historiaService->buscarPorUuid($uuid);

        if (Gate::denies('delete', $historia)) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->historiaService->removerHistoria($uuid);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return response()->json(
                'Erro ao remover história',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
