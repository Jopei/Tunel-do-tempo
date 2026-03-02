<?php

namespace App\Http\Controllers;

use App\DTO\NotificacaoCreateDTO;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Notificacoes\NotificacaoStoreRequest;

class NotificacaoController extends Controller
{
    public function __construct(
        protected NotificacaoService $service
    ) {}

    public function index(): JsonResponse
    {
        $usuarioId = auth()->user()->id;

        $notificacoes = $this->service->listarNaoLidas($usuarioId);

        return response()->json($notificacoes);
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
