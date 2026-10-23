<?php

namespace App\Services;

use App\DTO\CreateApiKeyDTO;
use App\Repositories\ApiKeyRepository;
use App\Models\ApiKey;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\AuthorizationException;

class ApiKeyService
{
    public function __construct(
        protected ApiKeyRepository $repository
    ) {}

    public function gerar(CreateApiKeyDTO $dto, bool $confiavel = false): array
    {
        $hashAntiga = hash('sha256', $dto->apiKeyAntiga);
        $existemChaves = $this->repository->existemChaves();
        $chaveValida = $this->repository->buscarAtivaPorHashEOrigem($hashAntiga, $dto->origem->value);

        // Primeira chave (bootstrap): so pelo comando artisan (confiavel) ou em ambiente local/testing.
        if (!$existemChaves && !$confiavel && !app()->environment(['local', 'testing'])) {
            throw new AuthorizationException('Geração da primeira API Key só via php artisan api-keys:gerar.');
        }

        if ($existemChaves && !$confiavel && !$chaveValida) {
            throw new AuthorizationException('API Key antiga inválida ou inativa');
        }

        $this->repository->desativarPorOrigem($dto->origem->value);

        $apiKeyNova = Str::uuid()->toString() . Str::random(32);
        $hashNova = hash('sha256', $apiKeyNova);

        $this->repository->criar([
            'nome' => $dto->nome,
            'hash' => $hashNova,
            'origem' => $dto->origem->value,
            'ativo' => true,
        ]);

        return [
            'api_key' => $apiKeyNova,
            'origem' => $dto->origem->value,
        ];
    }
}
