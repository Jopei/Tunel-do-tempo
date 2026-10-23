<?php

namespace App\Http\Controllers;

use App\DTO\CreateApiKeyDTO;
use App\Http\Controllers\Controller;
use App\Services\ApiKeyService;
use App\Http\Requests\CreateApiKeyRequest;
use Illuminate\Auth\Access\AuthorizationException;
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
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Operação não permitida.'], 403);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao gerar API Key'
            ], 500);
        }
    }
}
