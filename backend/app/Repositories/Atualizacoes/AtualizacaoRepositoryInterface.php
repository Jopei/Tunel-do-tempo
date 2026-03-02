<?php

namespace App\Repositories\Atualizacoes;

use App\Models\AtualizacaoRealizada;

interface AtualizacaoRepositoryInterface
{
    public function criar(array $dados): AtualizacaoRealizada;

    public function atualizar(AtualizacaoRealizada $atualizacao, array $dados): AtualizacaoRealizada;

    public function buscarPorUuid(string $uuid): ?AtualizacaoRealizada;

    public function listar(): array;
}