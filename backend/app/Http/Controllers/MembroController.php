<?php

namespace App\Http\Controllers;

use App\Enums\TipoHistoriaEnum;
use App\Enums\TipoUsuarioEnum;
use App\Http\Resources\HistoriaResumoResource;
use App\Http\Resources\MembroPublicoResource;
use App\Mapping\TipoUsuarioMapping;
use App\Mapping\UsuarioMapping;
use App\Models\Historia;
use App\Models\Usuario;
use App\Repositories\HistoriaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembroController extends Controller
{
    public function show(Request $request, HistoriaRepository $repo, string $slug): JsonResponse
    {
        $membro = Usuario::query()
            ->where(fn ($q) => $q->where(UsuarioMapping::SLUG, $slug)
                // fallback: uuid, apenas para membros ainda sem slug
                ->orWhere(fn ($q2) => $q2->whereNull(UsuarioMapping::SLUG)->where(UsuarioMapping::UUID, $slug)))
            ->whereHas('tipoUsuario', fn ($q) => $q->whereIn(TipoUsuarioMapping::NOME, [
                TipoUsuarioEnum::MEMBRO_ADM->value,
                TipoUsuarioEnum::MEMBRO_RUA->value,
            ]))
            ->with(['fotos' => fn ($q) => $q->whereNull('fotos.deleted_at'), 'fotos.tipo'])
            ->firstOrFail();

        $usuarioId = auth()->user()?->id;

        $daPessoa = fn () => $repo->comDadosDeFeed(
            Historia::query()
                ->whereHas('usuarios', fn ($q) => $q->where('usuarios.id', $membro->id))
                ->with(HistoriaRepository::usuariosComFoto())
                ->with('tipo:id,nome')
                ->withCount(HistoriaRepository::contagensMidia()),
            $usuarioId
        )
            ->orderByDesc('data_historia')
            ->orderByDesc('id');

        $historias = $daPessoa()
            ->whereHas('tipo', fn ($q) => $q->where('nome', '!=', TipoHistoriaEnum::PESSOAL->value))
            ->get();

        $pessoal = $daPessoa()
            ->whereHas('tipo', fn ($q) => $q->where('nome', TipoHistoriaEnum::PESSOAL->value))
            ->first();

        return response()->json([
            'uuid' => $membro->uuid,
            'slug' => $membro->slug,
            'nome' => $membro->nome,
            'foto_url' => MembroPublicoResource::fotoUrl($membro),
            'descricao' => $membro->descricao,
            'historias' => HistoriaResumoResource::collection($historias)->resolve($request),
            'historia_pessoal' => $pessoal
                ? (new HistoriaResumoResource($pessoal))->resolve($request) + ['conteudo' => $pessoal->conteudo]
                : null,
        ]);
    }
}
