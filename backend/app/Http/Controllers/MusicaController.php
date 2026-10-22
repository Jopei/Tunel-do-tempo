<?php

namespace App\Http\Controllers;

use App\Http\Requests\MusicaIndexRequest;
use App\Http\Requests\MusicaStoreRequest;
use App\Http\Requests\MusicaUpdateRequest;
use App\Http\Resources\MusicaResource;
use App\Services\MusicaService;
use App\DTO\MusicaCreateDTO;
use App\Mapping\MusicaMapping;
use App\Models\Musica;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\Storage;

class MusicaController extends Controller
{
    public function __construct(
        protected MusicaService $musicaService
    ) {}

    public function store(MusicaStoreRequest $request): JsonResponse
    {
        try {
            $dto = new MusicaCreateDTO(
                titulo: $request->titulo,
                musica: $request->file('musica'),
                artista: $request->input('artista'),
                album: $request->input('album'),
                ano: $request->filled('ano') ? (int) $request->input('ano') : null,
                duracaoSegundos: $request->filled('duracao_segundos') ? (int) $request->input('duracao_segundos') : null,
                capa: $request->file('capa'),
                capaFotoUuid: $request->input('capa_foto_uuid'),
            );

            $musica = $this->musicaService->criar($dto);

            return response()->json([
                'message' => 'Música criada com sucesso.',
                'uuid' => $musica->uuid,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Erro ao criar música.',
            ], 500);
        }
    }

    public function index(MusicaIndexRequest $request)
    {
        return MusicaResource::collection($this->musicaService->listar(
            $request->input('q'),
            $request->input('ordem', 'recentes'),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 24),
        ));
    }

    public function update(MusicaUpdateRequest $request, string $uuid): MusicaResource
    {
        $musica = $this->musicaService->atualizar($uuid, $request->validated(), $request->exists('capa_foto_uuid'));

        return new MusicaResource($musica);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->musicaService->excluir($uuid);

        return response()->json(['message' => 'Música removida com sucesso.']);
    }

    public function detalhes(string $uuid): MusicaResource
    {
        return new MusicaResource(Musica::where(MusicaMapping::UUID, $uuid)->with('capa')->firstOrFail());
    }

    public function show(string $uuid)
    {
        return $this->arquivo($uuid);
    }

    /** Stream via URL assinada temporaria (middleware "signed"); aceita Range/206. */
    public function stream(string $uuid)
    {
        return $this->arquivo($uuid, 'private, max-age=3600');
    }

    private function arquivo(string $uuid, string $cache = 'public, max-age=86400')
    {
        $musica = Musica::where(MusicaMapping::UUID, $uuid)->firstOrFail();

        // Novos arquivos ficam no disco privado; legados em "public" ate rodar musicas:mover-para-privado.
        $disco = collect([MusicaService::DISCO, 'public'])
            ->first(fn ($d) => $musica->path && Storage::disk($d)->exists($musica->path));

        if (!$disco) {
            abort(404);
        }

        return response()->file(
            Storage::disk($disco)->path($musica->path),
            [
                'Cache-Control' => $cache,
                'X-Musica-UUID' => $musica->uuid,
            ]
        );
    }
}
