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
}