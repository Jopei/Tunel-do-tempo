<?php

namespace App\Services;

use App\DTO\CreateApiKeyDTO;
use App\Repositories\ApiKeyRepository;
use App\Models\ApiKey;
use Illuminate\Support\Str;
use RuntimeException;

class ApiKeyService
{
    public function __construct(
        protected ApiKeyRepository $repository
    ) {}

    public function gerar(CreateApiKeyDTO $dto): array
    {
        $hashAntiga = hash('sha256', $dto->apiKeyAntiga);
        $existemChaves = $this->repository->existemChaves();
        $chaveValida = $this->repository->buscarAtivaPorHashEOrigem($hashAntiga, $dto->origem->value);

        if ($existemChaves && !$chaveValida) {
            throw new RuntimeException('API Key antiga inválida ou inativa');
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
