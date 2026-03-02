<?php

namespace App\Http\Controllers;

use App\Actions\Usuario\CriarUsuarioAction;
use App\Http\Requests\UsuarioStoreRequest;
use App\Services\UsuarioService;
use App\DTO\UsuarioCreateDTO;
use App\Http\Resources\UsuarioGetResource;
use App\Http\Resources\UsuarioListResource;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Throwable;

class UsuarioController extends Controller
{
    public function __construct(
        protected UsuarioService $usuarioService
    ) {}

    public function store(
        UsuarioStoreRequest $request,
        CriarUsuarioAction $action
    ) {
        try {
            $usuario = $action->execute($request->validated(), $request->file('imagem'));

            return response()->json(
                new UsuarioResource($usuario),
                201
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
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
