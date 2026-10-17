<?php

namespace App\Http\Controllers;

use App\DTO\NotificacaoCreateDTO;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Notificacoes\NotificacaoStoreRequest;
use App\Http\Requests\Notificacoes\NotificacaoIndexRequest;
use App\Http\Resources\NotificacaoResource;
use App\Http\Resources\NotificacaoEnviadaResource;

class NotificacaoController extends Controller
{
    public function __construct(
        protected NotificacaoService $service
    ) {}

    /**
     * Sem `status`/`page`: formato original (lista das nao lidas, sem paginacao).
     * Com `status` ou `page`: historico paginado com flag `lida`.
     */
    public function index(NotificacaoIndexRequest $request)
    {
        $usuarioId = auth()->user()->id;

        if (!$request->has('status') && !$request->has('page') && !$request->has('per_page')) {
            return response()->json($this->service->listarNaoLidas($usuarioId));
        }

        $notificacoes = $this->service->paginarParaUsuario(
            $usuarioId,
            $request->input('status', 'todas'),
            (int) $request->input('per_page', 15)
        );

        return NotificacaoResource::collection($notificacoes);
    }

    public function contagem(): JsonResponse
    {
        return response()->json([
            'nao_lidas' => $this->service->contarNaoLidas(auth()->user()->id),
        ]);
    }

    public function enviadas(NotificacaoIndexRequest $request)
    {
        return NotificacaoEnviadaResource::collection(
            $this->service->paginarEnviadas((int) $request->input('per_page', 15))
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->excluir($id);

        return response()->json(['message' => 'Notificação removida.']);
    }

    public function marcar(int $id): JsonResponse
    {

        $this->service->marcarComoLida(auth()->user()->id, $id);

        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function marcarTodas(): JsonResponse
    {
        $this->service->marcarTodasComoLidas(auth()->user()->id);

        return response()->json(['message' => 'Todas notificações foram marcadas como lidas.']);
    }

    public function store(NotificacaoStoreRequest $request): JsonResponse
    {
        $dto = new NotificacaoCreateDTO(
            titulo: $request->titulo,
            descricao: $request->descricao,
            tema: $request->tema,
            link: $request->link
        );

        $notificacao = $this->service->criar($dto);

        return response()->json($notificacao, 201);
    }
}
