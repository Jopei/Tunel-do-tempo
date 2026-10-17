<?php

namespace App\Http\Controllers;

use App\Enums\TipoUsuarioEnum;
use App\Http\Requests\LembrancaStoreRequest;
use App\Http\Requests\PaginacaoRequest;
use App\Http\Resources\LembrancaResource;
use App\Mapping\HistoriaMapping;
use App\Mapping\LembrancaMapping;
use App\Models\Historia;
use App\Models\Lembranca;
use App\Support\Sanitizador;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LembrancaController extends Controller
{
    public function index(PaginacaoRequest $request, string $uuid)
    {
        $historia = Historia::where(HistoriaMapping::UUID, $uuid)->firstOrFail();

        return $this->listar(
            Lembranca::where(LembrancaMapping::HISTORIA_ID, $historia->id)
                ->where(LembrancaMapping::CONTEXTO, LembrancaMapping::CONTEXTO_HISTORIA),
            $request
        );
    }

    public function store(LembrancaStoreRequest $request, string $uuid): JsonResponse
    {
        $historia = Historia::where(HistoriaMapping::UUID, $uuid)->firstOrFail();

        return $this->criar($request, $historia->id, LembrancaMapping::CONTEXTO_HISTORIA);
    }

    public function indexHomenagem(PaginacaoRequest $request)
    {
        return $this->listar(
            Lembranca::whereNull(LembrancaMapping::HISTORIA_ID)
                ->where(LembrancaMapping::CONTEXTO, LembrancaMapping::CONTEXTO_HOMENAGEM),
            $request
        );
    }

    public function storeHomenagem(LembrancaStoreRequest $request): JsonResponse
    {
        return $this->criar($request, null, LembrancaMapping::CONTEXTO_HOMENAGEM);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $lembranca = Lembranca::where(LembrancaMapping::UUID, $uuid)->firstOrFail();
        $usuario = auth()->user();

        $ehAutor = $lembranca->usuario_id === $usuario->id;
        $ehAdm = $usuario->tipoUsuario?->nome === TipoUsuarioEnum::MEMBRO_ADM->value;

        if (!$ehAutor && !$ehAdm) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        $lembranca->delete();

        return response()->json(null, 204);
    }

    private function listar($query, PaginacaoRequest $request)
    {
        $lembrancas = $query
            ->with('usuario:id,uuid,nome')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 15));

        return LembrancaResource::collection($lembrancas);
    }

    private function criar(LembrancaStoreRequest $request, ?int $historiaId, string $contexto): JsonResponse
    {
        $texto = Sanitizador::texto($request->input('texto'));

        if (mb_strlen($texto) < 2) {
            return response()->json([
                'message' => 'The texto field is invalid.',
                'errors' => ['texto' => ['A lembrança deve ter ao menos 2 caracteres de texto.']],
            ], 422);
        }

        $lembranca = Lembranca::create([
            LembrancaMapping::UUID => (string) Str::uuid(),
            LembrancaMapping::HISTORIA_ID => $historiaId,
            LembrancaMapping::CONTEXTO => $contexto,
            LembrancaMapping::USUARIO_ID => auth()->user()->id,
            LembrancaMapping::TEXTO => $texto,
        ]);

        $lembranca->load('usuario:id,uuid,nome');

        return response()->json(new LembrancaResource($lembranca), 201);
    }
}
