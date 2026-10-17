<?php

namespace App\Http\Controllers;

use App\Http\Resources\MembroPublicoResource;
use App\Enums\TipoHistoriaEnum;
use App\Models\Historia;
use App\Repositories\UsuarioRepository;
use App\Support\Sanitizador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicoController extends Controller
{
    public function membros(UsuarioRepository $usuarios)
    {
        return response()->json(
            MembroPublicoResource::collection($usuarios->listarMembros())
        );
    }

    public function historiasRecentes(Request $request)
    {
        $limite = max(1, min(10, (int) $request->query('limite', 3)));

        $historias = Historia::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', '!=', TipoHistoriaEnum::PESSOAL->value))
            ->with(['fotos' => fn ($q) => $q->select('fotos.id', 'fotos.uuid')->whereNull('fotos.deleted_at')])
            ->orderByDesc('data_historia')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        return response()->json($historias->map(fn ($h) => [
            'uuid' => $h->uuid,
            'titulo' => $h->titulo,
            'descricao_curta' => Str::limit(Sanitizador::texto(html_entity_decode((string) $h->descricao_curta)), 160),
            'data_historia' => $h->data_historia ? Carbon::parse($h->data_historia)->toDateString() : null,
            'capa_url' => ($capa = $h->fotos->first()) ? route('api.fotos.show', $capa->uuid) : null,
        ])->values());
    }
}
