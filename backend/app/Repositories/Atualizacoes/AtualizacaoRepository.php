<?php

namespace App\Repositories\Atualizacoes;

use App\Models\AtualizacaoRealizada;
use App\Mapping\AtualizacaoMapping;

class AtualizacaoRepository implements AtualizacaoRepositoryInterface
{
    public function criar(array $dados): AtualizacaoRealizada
    {
        return AtualizacaoRealizada::create($dados);
    }

    public function atualizar(AtualizacaoRealizada $atualizacao, array $dados): AtualizacaoRealizada
    {
        $atualizacao->update($dados);
        return $atualizacao;
    }

    public function buscarPorUuid(string $uuid): ?AtualizacaoRealizada
    {
        return AtualizacaoRealizada::where(AtualizacaoMapping::UUID, $uuid)->first();
    }

    public function listar(): array
    {
        return AtualizacaoRealizada::with('usuario')
            ->latest()
            ->get()
            ->all();
    }
}