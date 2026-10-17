<?php

namespace App\Http\Controllers;

use App\Http\Requests\EsqueciSenhaRequest;
use App\Http\Requests\RedefinirSenhaRequest;
use App\Services\RedefinicaoSenhaService;
use Illuminate\Http\JsonResponse;

class SenhaController extends Controller
{
    public function __construct(
        protected RedefinicaoSenhaService $service
    ) {}

    public function esqueci(EsqueciSenhaRequest $request): JsonResponse
    {
        $this->service->solicitar($request->input('email'));

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, enviaremos as instruções para redefinir a senha.',
        ]);
    }

    public function redefinir(RedefinirSenhaRequest $request): JsonResponse
    {
        $ok = $this->service->redefinir(
            $request->input('email'),
            $request->input('token'),
            $request->input('senha')
        );

        if (!$ok) {
            return response()->json(['message' => 'Link inválido ou expirado. Solicite uma nova redefinição.'], 422);
        }

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }
}
