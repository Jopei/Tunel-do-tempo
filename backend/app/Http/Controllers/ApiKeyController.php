<?php

namespace App\Http\Controllers;

use App\DTO\CreateApiKeyDTO;
use App\Http\Controllers\Controller;
use App\Services\ApiKeyService;
use App\Http\Requests\CreateApiKeyRequest;
use Throwable;

class ApiKeyController extends Controller
{
    public function __construct(
        protected ApiKeyService $service
    ) {}

    public function gerar(CreateApiKeyRequest $request)
    {
        try {
            $dto = new CreateApiKeyDTO(
                $request->getOrigem(),
                $request->getNome(),
                $request->getApiKeyAntiga()
            );

            return response()->json(
                $this->service->gerar($dto)
            );
        } catch (Throwable $e) {
            dd($e->getMessage());
            return response()->json([
                'message' => 'Erro ao gerar API Key'
            ], 500);
        }
    }
}
