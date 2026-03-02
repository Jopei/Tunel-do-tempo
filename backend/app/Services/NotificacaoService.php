<?php

namespace App\Services;

use App\DTO\NotificacaoCreateDTO;
use App\Repositories\NotificacaoRepository;
use App\Mapping\NotificacaoUsuarioMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificacaoService
{
    public function __construct(
        protected NotificacaoRepository $repository
    ) {}

    public function criar(NotificacaoCreateDTO $dto)
    {
        $usuarioId = auth()->user()->id;
        return $this->repository->create([
            'uuid' => Str::uuid(),
            'titulo' => $dto->titulo,
            'descricao' => $dto->descricao,
            'tema' => $dto->tema,
            'link' => $dto->link,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function listarNaoLidas(int $usuarioId)
    {
        return $this->repository->buscarNaoLidas($usuarioId);
    }

    public function marcarComoLida(int $usuarioId, int $notificacaoId): void
    {
        DB::table(NotificacaoUsuarioMapping::MODEL_TABLE_NAME)
            ->updateOrInsert(
                [
                    NotificacaoUsuarioMapping::USUARIO_ID => $usuarioId,
                    NotificacaoUsuarioMapping::NOTIFICACAO_ID => $notificacaoId,
                ],
                [
                    NotificacaoUsuarioMapping::LIDA_EM => now(),
                    NotificacaoUsuarioMapping::UPDATED_AT => now(),
                    NotificacaoUsuarioMapping::CREATED_AT => now(),
                ]
            );
    }

    public function marcarTodasComoLidas(int $usuarioId): void
    {
        $notificacoes = $this->listarNaoLidas($usuarioId);

        foreach ($notificacoes as $notificacao) {
            $this->marcarComoLida($usuarioId, $notificacao->id);
        }
    }
}