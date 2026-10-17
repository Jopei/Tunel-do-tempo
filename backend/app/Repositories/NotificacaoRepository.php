<?php

namespace App\Repositories;

use App\Models\Notificacao;

class NotificacaoRepository
{
    public function create(array $data): Notificacao
    {
        return Notificacao::create($data);
    }

    public function buscarNaoLidas(int $usuarioId)
    {
        return Notificacao::whereDoesntHave('usuarios', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId)
                  ->whereNotNull('lida_em');
        })
        ->orderByDesc('created_at')
        ->get();
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        return Notificacao::whereDoesntHave('usuarios', function ($query) use ($usuarioId) {
            $query->where('usuario_id', $usuarioId)
                  ->whereNotNull('lida_em');
        })->count();
    }

    /**
     * Historico do usuario com flag `lida`. $status: 'todas' | 'nao_lidas'.
     */
    public function paginarParaUsuario(int $usuarioId, string $status, int $perPage)
    {
        $query = Notificacao::query()
            ->withExists(['usuarios as lida' => function ($q) use ($usuarioId) {
                $q->where('usuario_id', $usuarioId)->whereNotNull('lida_em');
            }]);

        if ($status === 'nao_lidas') {
            $query->whereDoesntHave('usuarios', function ($q) use ($usuarioId) {
                $q->where('usuario_id', $usuarioId)->whereNotNull('lida_em');
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Notificacoes enviadas (visao admin) com quantidade de leitores.
     */
    public function paginarEnviadas(int $perPage)
    {
        return Notificacao::query()
            ->with('autor:id,uuid,nome')
            ->withCount(['usuarios as lidas_count' => fn ($q) => $q->whereNotNull('lida_em')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function excluir(int $id): bool
    {
        $notificacao = Notificacao::findOrFail($id);

        return (bool) $notificacao->delete();
    }
}
