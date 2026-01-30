<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioStoreRequest;
use App\Services\UsuarioService;
use App\DTO\UsuarioCreateDTO;
use App\Http\Resources\UsuarioGetResource;
use App\Http\Resources\UsuarioListResource;
use Illuminate\Http\JsonResponse;
use Throwable;

class UsuarioController extends Controller
{
    public function __construct(
        protected UsuarioService $usuarioService
    ) {}

    public function store(UsuarioStoreRequest $request): JsonResponse
    {
        try {
            $dto = new UsuarioCreateDTO(
                nome: $request->nome,
                email: $request->email,
                senha: $request->senha,
                tipoUsuarioId: $request->tipo_usuario_id,
                aniversario: $request->aniversario,
                telefone: $request->telefone,
                descricao: $request->descricao,
            );

            $usuario = $this->usuarioService->criarUsuario($dto);

            return response()->json([
                'message' => 'Usuário criado com sucesso.'
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar usuário.',
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $usuarios = $this->usuarioService->listarUsuarios();

            return response()->json(
                UsuarioListResource::collection($usuarios)
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários.'
            ], 500);
        }
    }


    public function getUsuario(string $uuid): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->getUsuarioCompletoPorUuid($uuid);

            return response()->json(
                new UsuarioGetResource($usuario)
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], 404);
        }
    }
}
