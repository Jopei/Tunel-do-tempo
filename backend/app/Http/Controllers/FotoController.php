<?php

namespace App\Http\Controllers;

use App\Actions\Fotos\IndexFotoAction;
use App\DTO\FotoCreateDTO;
use App\DTO\FotoIndexDTO;
use App\DTO\FotoPerfilCreateDTO;
use App\DTO\FotoUpdateDTO;
use App\Http\Requests\FotoPerfilStoreRequest;
use App\Http\Requests\FotoLoteExcluirRequest;
use App\Http\Requests\FotoStoreRequest;
use App\Repositories\FotoRepository;
use App\Http\Requests\FotoUpdateRequest;
use App\Http\Requests\FotoUsuarioSyncRequest;
use App\Http\Resources\FotoResource;
use App\Models\Foto;
use App\Mapping\FotoMapping;
use App\Services\FotoService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\FotoIndexRequest;
use Illuminate\Http\Request;
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
                criadoEm: $request->criado_em,
                dataFoto: $request->input('data_foto'),
            );

            $foto = $this->fotoService->criarFoto($dto);

            return response()->json([
                'message' => 'Foto criada com sucesso.',
                'uuid' => $foto->uuid,
            ], 201);
        } catch (Throwable $e) {
            report($e);

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
            report($e);

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

    public function index(FotoIndexRequest $request)
    {
        $dto = new FotoIndexDTO(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            q: $request->input('q'),
            ano: $request->filled('ano') ? (int) $request->input('ano') : null,
            usuarioUuid: $request->input('usuario_uuid'),
            ordem: $request->input('ordem', 'recentes'),
            agrupar: $request->input('agrupar'),
            albumUuid: $request->input('album_uuid'),
            de: $request->input('de'),
            ate: $request->input('ate'),
        );

        $fotos = $this->fotoIndexAction->executar($dto);

        return FotoResource::collection($fotos);
    }

    public function linhaDoTempo(FotoIndexRequest $request, FotoRepository $fotos): JsonResponse
    {
        $dto = new FotoIndexDTO(
            q: $request->input('q'),
            ano: $request->filled('ano') ? (int) $request->input('ano') : null,
            usuarioUuid: $request->input('usuario_uuid'),
            albumUuid: $request->input('album_uuid'),
            de: $request->input('de'),
            ate: $request->input('ate'),
        );

        return response()->json($fotos->linhaDoTempo($dto));
    }

    /** Publico: so agregados (ano => total), como a linha do tempo. */
    public function anos(FotoRepository $fotos): JsonResponse
    {
        return response()->json($fotos->anos());
    }

    /** Login: fotos do mesmo dia/mes de hoje em anos anteriores, agrupadas por ano (desc). */
    public function nesteDia(Request $request, FotoRepository $fotos): JsonResponse
    {
        $dados = $request->validate(['limite' => ['nullable', 'integer', 'between:1,60']]);

        $grupos = $fotos->nesteDia((int) ($dados['limite'] ?? 24))
            ->groupBy('ano_ref')
            ->map(fn ($itens, $ano) => [
                'ano' => (int) $ano,
                'fotos' => FotoResource::collection($itens)->resolve($request),
            ])
            ->sortByDesc('ano')
            ->values();

        return response()->json($grupos);
    }

    public function loteExcluir(FotoLoteExcluirRequest $request): JsonResponse
    {
        $resultado = $this->fotoService->removerLote($request->input('uuids'));

        return response()->json([
            'message' => $resultado['excluidas'] . ' foto(s) removida(s).',
            'excluidas' => $resultado['excluidas'],
            'nao_encontradas' => $resultado['nao_encontradas'],
        ]);
    }

    public function details(string $uuid, FotoRepository $fotos)
    {
        $foto = $this->fotoService->buscarPorUuid($uuid);

        $foto->load(array_merge(['albuns', 'historias.tipo'], $fotos->relacoesLogado()));

        $capas = $fotos->capasDeHistorias($foto->historias->pluck('id')->all());
        foreach ($foto->historias as $historia) {
            $historia->setAttribute('capa_url', $capas[$historia->id] ?? null);
        }

        return new FotoResource($foto);
    }

    public function update(FotoUpdateRequest $request, string $uuid): JsonResponse
    {
        try {

            $dto = new FotoUpdateDTO(
                uuid: $uuid,
                titulo: $request->titulo,
                descricao: $request->descricao,
                dataFoto: $request->input('data_foto'),
                informouDataFoto: $request->exists('data_foto'),
            );

            $this->fotoService->editarFoto($dto);

            return response()->json([
                'message' => 'Foto atualizada com sucesso.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Foto não encontrada.'], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao atualizar foto.'
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
        } catch (ModelNotFoundException $e) {

            return response()->json(['message' => 'Foto não encontrada.'], 404);
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
