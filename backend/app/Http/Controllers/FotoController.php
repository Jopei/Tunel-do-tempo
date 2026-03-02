<?php

namespace App\Http\Controllers;

use App\Actions\Fotos\IndexFotoAction;
use App\DTO\FotoCreateDTO;
use App\DTO\FotoIndexDTO;
use App\DTO\FotoPerfilCreateDTO;
use App\DTO\FotoUpdateDTO;
use App\Http\Requests\FotoPerfilStoreRequest;
use App\Http\Requests\FotoStoreRequest;
use App\Http\Requests\FotoUpdateRequest;
use App\Http\Requests\FotoUsuarioSyncRequest;
use App\Http\Resources\FotoResource;
use App\Models\Foto;
use App\Mapping\FotoMapping;
use App\Services\FotoService;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class FotoController extends Controller
{
    public function __construct(
        protected FotoService $fotoService,
        protected IndexFotoAction $fotoIndexAction
    ) {}
    public function show(string $uuid)
    {
        $foto = Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();

        if ($foto->external_link) {
            return redirect()->away($foto->external_link);
        }

        if (!Storage::disk('public')->exists($foto->path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($foto->path),
            [
                'Cache-Control' => 'public, max-age=86400',
                'X-Foto-UUID' => $foto->uuid
            ]
        );
    }

    public function store(FotoStoreRequest $request): JsonResponse
    {
        try {
            $dto = new FotoCreateDTO(
                imagem: $request->file('imagem'),
                tipoImagemId: $request->tipo_imagem_id,
                usuariosUuid: $request->usuarios_uuid,
                titulo: $request->titulo,
                descricao: $request->descricao,
            );

            $foto = $this->fotoService->criarFoto($dto);

            return response()->json([
                'message' => 'Foto criada com sucesso.',
                'uuid' => $foto->uuid,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar foto',
            ], 500);
        }
    }


    public function storeFotoPerfil(FotoPerfilStoreRequest $request): JsonResponse
    {
        try {
            $dto = new FotoPerfilCreateDTO(
                imagem: $request->file('imagem'),
                usuarioUuid: $request->usuario_uuid,
                titulo: $request->titulo,
                descricao: $request->descricao,
            );

            $foto = $this->fotoService->criarFotoPerfil($dto);

            return response()->json([
                'message' => 'Foto de perfil criada com sucesso.',
                'uuid' => $foto->uuid,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao criar foto de perfil.',
            ], 500);
        }
    }
    public function syncUsuarios(
        FotoUsuarioSyncRequest $request,
        string $fotoUuid
    ): JsonResponse {
        try {
            $this->fotoService->sincronizarUsuarios(
                $fotoUuid,
                $request->usuarios_uuid ?? []
            );

            return response()->json([
                'message' => 'Relações atualizadas com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao atualizar relações.',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $dto = new FotoIndexDTO(
            page: $request->page ?? 1,
            perPage: $request->per_page ?? 15
        );

        $fotos = $this->fotoIndexAction->executar($dto);

        return FotoResource::collection($fotos);
    }

    public function details(string $uuid)
    {
        $foto = $this->fotoService->buscarPorUuid($uuid);

        return new FotoResource($foto);
    }

    public function update(FotoUpdateRequest $request, string $uuid): JsonResponse
    {
        try {

            $dto = new FotoUpdateDTO(
                uuid: $uuid,
                titulo: $request->titulo,
                descricao: $request->descricao
            );

            $this->fotoService->editarFoto($dto);

            return response()->json([
                'message' => 'Foto atualizada com sucesso.'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao atualizar foto.' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {

            $this->fotoService->removerFoto($uuid);

            return response()->json([
                'message' => 'Foto removida com sucesso.'
            ], 200);
        } catch (DomainException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (Throwable $e) {

            return response()->json([
                'message' => 'Erro ao remover foto.'
            ], 500);
        }
    }

    
}
