<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Mapping\ApiKeyMapping;

class ApiKeyRepository
{
    public function existemChaves(): bool
    {
        return ApiKey::query()->exists();
    }

    public function buscarAtivaPorHashEOrigem(string $hash, string $origem): ?ApiKey
    {
        return ApiKey::query()
            ->where(ApiKeyMapping::HASH, $hash)
            ->where(ApiKeyMapping::ORIGEM, $origem)
            ->where(ApiKeyMapping::ATIVO, true)
            ->first();
    }
    public function desativarPorOrigem(string $origem): void
    {
        ApiKey::query()
            ->where(ApiKeyMapping::ORIGEM, $origem)
            ->where(ApiKeyMapping::ATIVO, true)
            ->update([
                ApiKeyMapping::ATIVO => false,
            ]);
    }

    public function criar(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    public function listarAtivas()
    {
        return ApiKey::query()
            ->where(ApiKeyMapping::ATIVO, true)
            ->get();
    }
}
