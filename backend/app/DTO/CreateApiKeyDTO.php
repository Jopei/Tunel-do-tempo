<?php

namespace App\DTO;

use App\Enums\ApiKeyOrigemEnum;

class CreateApiKeyDTO
{
    public function __construct(
        public readonly ApiKeyOrigemEnum $origem,
        public readonly ?string $nome,
        public readonly string $apiKeyAntiga,
    ) {}
}
