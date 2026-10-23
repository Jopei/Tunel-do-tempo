<?php

namespace App\Console\Commands;

use App\DTO\CreateApiKeyDTO;
use App\Enums\ApiKeyOrigemEnum;
use App\Services\ApiKeyService;
use Illuminate\Console\Command;

class GerarApiKey extends Command
{
    protected $signature = 'api-keys:gerar {origem=API : TESTER ou API} {--nome=}';

    protected $description = 'Gera uma API Key (desativa as anteriores da mesma origem). Unica forma de criar a primeira chave em producao.';

    public function handle(ApiKeyService $service): int
    {
        $origem = ApiKeyOrigemEnum::tryFrom((string) $this->argument('origem'));

        if (!$origem) {
            $this->error('Origem inválida. Use: ' . implode(', ', array_column(ApiKeyOrigemEnum::cases(), 'value')));

            return self::FAILURE;
        }

        $r = $service->gerar(new CreateApiKeyDTO($origem, $this->option('nome'), ''), true);
        $this->info('API Key (guarde agora, não é exibida de novo):');
        $this->line($r['api_key']);

        return self::SUCCESS;
    }
}
