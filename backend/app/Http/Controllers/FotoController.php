<?php

namespace App\Http\Controllers;

use App\DTO\FotoCreateDTO;
use App\DTO\FotoPerfilCreateDTO;
use App\Http\Requests\FotoPerfilStoreRequest;
use App\Http\Requests\FotoStoreRequest;
use App\Http\Requests\FotoUpdateRequest;
use App\Http\Requests\FotoUsuarioSyncRequest;
use App\Models\Foto;
use App\Mapping\FotoMapping;
use App\Services\FotoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Throwable;

class FotoController extends Controller
{
    public function __construct(
        protected FotoService $fotoService
    ) {}
    public function show(string $uuid)
    {
        $foto = Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();

        if ($foto->external_link) {
            return redirect()->away($foto->external_link);
        }

        if (!Storage::exists($foto->path)) {
            abort(404);
        }

        $mime = Storage::mimeType($foto->path);

        return response()->file(
            Storage::path($foto->path),
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
                'message' => 'Erro ao criar foto.',
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

    public function update(FotoUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $this->fotoService->editarFoto(
                $uuid,
                $request->validated()
            );

            return response()->json([
                'message' => 'Foto atualizada com sucesso.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Erro ao atualizar foto.',
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
}
