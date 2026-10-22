<?php

namespace App\Http\Controllers;

use App\Actions\Usuario\CriarUsuarioAction;
use App\DTO\Usuario\AtualizarUsuarioDTO;
use App\Http\Requests\UsuarioStoreRequest;
use App\Services\UsuarioService;
use App\Services\FotoService;
use App\Enums\TipoUsuarioEnum;
use Illuminate\Auth\Access\AuthorizationException;
use App\DTO\UsuarioCreateDTO;
use App\Http\Requests\Usuario\AtualizarFotoPerfilRequest;
use App\Http\Requests\Usuario\AtualizarUsuarioRequest;
use App\Http\Resources\UsuarioGetResource;
use App\Http\Resources\UsuarioListResource;
use App\Http\Resources\UsuarioResource;
use App\Http\Resources\UsuarioAuthResource;
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

    public function me(): JsonResponse
    {
        $usuario = auth()->user()->load(['tipoUsuario', 'fotos.tipo']);

        return response()->json(new UsuarioAuthResource($usuario));
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

    public function atualizar(
        AtualizarUsuarioRequest $request,
        UsuarioService $service,
        string $uuid
    ): JsonResponse {
        $dto = new AtualizarUsuarioDTO(
            uuid: $uuid,
            nome: $request->nome,
            email: $request->email,
            senha: $request->senha,
            aniversario: $request->aniversario,
            telefone: $request->telefone,
            descricao: $request->descricao
        );

        $usuario = $service->atualizar($dto, auth()->user());

        return response()->json($usuario);
    }

    public function atualizarFotoPerfil(
        AtualizarFotoPerfilRequest $request,
        UsuarioService $service,
        FotoService $fotoService,
        string $uuid
    ): JsonResponse {
        $logado = auth()->user();

        if ($logado->uuid !== $uuid && $logado->tipoUsuario?->nome !== TipoUsuarioEnum::MEMBRO_ADM->value) {
            throw new AuthorizationException('Você não pode alterar a foto de outro usuário.');
        }

        $foto = $fotoService->definirFotoPerfil(
            $service->buscarUsuarioPorUuid($uuid),
            $request->file('foto')
        );

        return response()->json([
            'mensagem' => 'Foto atualizada com sucesso.',
            'foto' => new \App\Http\Resources\FotoPerfilResource($foto)
        ]);
    }
}
