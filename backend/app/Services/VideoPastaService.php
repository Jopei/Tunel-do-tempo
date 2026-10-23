<?php

namespace App\Services;

use App\Mapping\VideoMapping;
use App\Mapping\VideoPastaMapping;
use App\Models\Video;
use App\Models\VideoPasta;
use DomainException;
use Illuminate\Validation\ValidationException;

class VideoPastaService
{
    /**
     * @return array{pasta_atual:?VideoPasta, caminho:array<int,array{uuid:string,nome:string}>, pastas:\Illuminate\Support\Collection}
     */
    public function listar(?string $paiUuid): array
    {
        $pai = $paiUuid ? $this->buscar($paiUuid) : null;

        $pastas = VideoPasta::query()
            ->with('pai:id,uuid')
            ->withCount('videos')
            ->when($pai, fn ($q) => $q->where(VideoPastaMapping::PAI_ID, $pai->id), fn ($q) => $q->whereNull(VideoPastaMapping::PAI_ID))
            ->orderBy(VideoPastaMapping::NOME)
            ->get();

        return ['pasta_atual' => $pai, 'caminho' => $this->caminho($pai), 'pastas' => $pastas];
    }

    public function buscar(string $uuid): VideoPasta
    {
        return VideoPasta::where(VideoPastaMapping::UUID, $uuid)->firstOrFail();
    }

    public function criar(string $nome, ?string $paiUuid): VideoPasta
    {
        $nome = trim($nome);
        $pai = $paiUuid ? $this->buscar($paiUuid) : null;
        $this->garantirNomeLivre($nome, $pai?->id);

        return VideoPasta::create([
            VideoPastaMapping::UUID => (string) \Illuminate\Support\Str::uuid(),
            VideoPastaMapping::NOME => $nome,
            VideoPastaMapping::PAI_ID => $pai?->id,
            VideoPastaMapping::USUARIO_CADASTROU_ID => auth()->user()->id,
        ]);
    }

    public function atualizar(string $uuid, array $dados, bool $informouPai): VideoPasta
    {
        $pasta = $this->buscar($uuid);

        $nome = array_key_exists('nome', $dados) ? trim($dados['nome']) : $pasta->nome;
        $paiId = $pasta->pai_id;

        if ($informouPai) {
            $pai = !empty($dados['pai_uuid']) ? $this->buscar($dados['pai_uuid']) : null;

            if ($pai && ($pai->id === $pasta->id || $this->ehDescendente($pai, $pasta))) {
                throw ValidationException::withMessages([
                    'pai_uuid' => ['Não é possível mover uma pasta para dentro dela mesma ou de uma subpasta.'],
                ]);
            }

            $paiId = $pai?->id;
        }

        $this->garantirNomeLivre($nome, $paiId, $pasta->id);

        $pasta->update([VideoPastaMapping::NOME => $nome, VideoPastaMapping::PAI_ID => $paiId]);

        return $pasta->refresh();
    }

    public function excluir(string $uuid): void
    {
        $pasta = $this->buscar($uuid);

        if ($pasta->filhas()->exists() || $pasta->videos()->exists()) {
            throw new DomainException('A pasta não está vazia. Mova ou exclua os vídeos e subpastas antes.');
        }

        // Pasta vazia: remocao definitiva evita colidir com unique(pai_id, nome) ao recriar o nome.
        $pasta->forceDelete();
    }

    public function moverVideo(string $videoUuid, ?string $pastaUuid): ?VideoPasta
    {
        $video = Video::where(VideoMapping::UUID, $videoUuid)->firstOrFail();
        $pasta = $pastaUuid ? $this->buscar($pastaUuid) : null;

        $video->update([VideoMapping::PASTA_ID => $pasta?->id]);

        return $pasta;
    }

    /** Raiz -> pasta atual (inclusive). */
    private function caminho(?VideoPasta $pasta): array
    {
        $caminho = [];

        while ($pasta) {
            array_unshift($caminho, ['uuid' => $pasta->uuid, 'nome' => $pasta->nome]);
            $pasta = $pasta->pai_id ? VideoPasta::find($pasta->pai_id) : null;
        }

        return $caminho;
    }

    /** $candidata esta dentro de $ancestral (em qualquer nivel)? */
    private function ehDescendente(VideoPasta $candidata, VideoPasta $ancestral): bool
    {
        $atual = $candidata;
        $visitadas = [];

        while ($atual && $atual->pai_id && !in_array($atual->id, $visitadas, true)) {
            if ($atual->pai_id === $ancestral->id) {
                return true;
            }
            $visitadas[] = $atual->id;
            $atual = VideoPasta::find($atual->pai_id);
        }

        return false;
    }

    private function garantirNomeLivre(string $nome, ?int $paiId, ?int $ignorarId = null): void
    {
        $existe = VideoPasta::query()
            ->where(VideoPastaMapping::NOME, $nome)
            ->when($paiId, fn ($q) => $q->where(VideoPastaMapping::PAI_ID, $paiId), fn ($q) => $q->whereNull(VideoPastaMapping::PAI_ID))
            ->when($ignorarId, fn ($q) => $q->where(VideoPastaMapping::ID, '!=', $ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages(['nome' => ['Já existe uma pasta com esse nome neste local.']]);
        }
    }
}
