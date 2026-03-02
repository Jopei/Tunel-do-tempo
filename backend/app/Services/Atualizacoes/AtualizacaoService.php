<?php

namespace App\Services\Atualizacoes;

use App\DTO\Atualizacoes\AtualizacaoDTO;
use App\Repositories\Atualizacoes\AtualizacaoRepositoryInterface;
use App\Models\AtualizacaoRealizada;
use App\Mapping\AtualizacaoMapping;

class AtualizacaoService
{
    public function __construct(
        private AtualizacaoRepositoryInterface $repository
    ) {}

    public function criar(AtualizacaoDTO $dto): AtualizacaoRealizada
    {
        return $this->repository->criar([
            AtualizacaoMapping::TITULO => $dto->titulo,
            AtualizacaoMapping::CONTEUDO_MARKDOWN => $dto->conteudoMarkdown,
            AtualizacaoMapping::LINK_MUSICA => $dto->linkMusica,
            AtualizacaoMapping::USUARIO_CADASTRADO => $dto->usuarioId,
        ]);
    }

    public function atualizar(AtualizacaoRealizada $atualizacao, AtualizacaoDTO $dto): AtualizacaoRealizada
    {
        if ($atualizacao->{AtualizacaoMapping::USUARIO_CADASTRADO} !== auth()->user()->id) {
            abort(403, 'Você não pode editar esta atualização.');
        }

        return $this->repository->atualizar($atualizacao, [
            AtualizacaoMapping::TITULO => $dto->titulo,
            AtualizacaoMapping::CONTEUDO_MARKDOWN => $dto->conteudoMarkdown,
            AtualizacaoMapping::LINK_MUSICA => $dto->linkMusica,
        ]);
    }
}