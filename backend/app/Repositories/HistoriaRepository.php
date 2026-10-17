<?php

namespace App\Repositories;

use App\Mapping\FotoMapping;
use App\Models\Historia;
use App\Mapping\HistoriaMapping;
use Illuminate\Support\Str;
use App\Support\Sanitizador;

class HistoriaRepository
{
    public function buscarHistoriasPorLimite(int $limite)
    {
        return Historia::query()
            ->with([
                'fotos' => function ($query) {
                    $query->select(FotoMapping::ID, FotoMapping::PATH);
                }
            ])
            ->select(
                HistoriaMapping::ID,
                HistoriaMapping::UUID
            )
            ->inRandomOrder()
            ->limit($limite)
            ->get();
    }

    public function create(array $data): Historia
    {
        $data[HistoriaMapping::UUID] = Str::uuid();

        return Historia::create($data);
    }

    public function buscarPorUuidComUsuarios(string $uuid): Historia
    {
        return Historia::where(HistoriaMapping::UUID, $uuid)
            ->with(
                'usuarios:id,uuid,nome',
                'tipo:id,nome'
            )
            ->firstOrFail();
    }

    public function buscarCompletaPorUuid(string $uuid, ?int $usuarioId = null): Historia
    {
        return Historia::where(HistoriaMapping::UUID, $uuid)
            ->with([
                'tipo:id,nome',
                'usuarios:id,uuid,nome',
                'fotos' => fn ($q) => $q->select('fotos.id', 'fotos.uuid', 'fotos.titulo', 'fotos.descricao')
                    ->whereNull('fotos.deleted_at'),
                'videos' => fn ($q) => $q->select('videos.id', 'videos.uuid', 'videos.titulo', 'videos.thumbnail')
                    ->whereNull('videos.deleted_at'),
                'musicas' => fn ($q) => $q->select('musicas.id', 'musicas.uuid', 'musicas.titulo')
                    ->whereNull('musicas.deleted_at'),
            ])
            ->withCount('lembrancas')
            ->when($usuarioId, fn ($q) => $q->withExists([
                'favoritos as favoritado' => fn ($f) => $f->where('usuario_id', $usuarioId),
            ]))
            ->firstOrFail();
    }

    /**
     * Resumo com contagem de midia e filtros opcionais (q, tipo, usuario).
     * Sem $perPage devolve Collection; com $perPage devolve paginator.
     */
    public function listarResumo(array $filtros = [], ?int $perPage = null)
    {
        $query = Historia::with('usuarios:id,uuid,nome', 'tipo:id,nome')
            ->withCount(self::contagensMidia());

        $this->aplicarFiltros($query, $filtros);

        $query->orderBy('data_historia', 'desc')->orderByDesc('id');

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    public function favoritasDoUsuario(int $usuarioId, int $perPage)
    {
        return Historia::with('usuarios:id,uuid,nome', 'tipo:id,nome')
            ->withCount(self::contagensMidia())
            ->whereHas('favoritos', fn ($q) => $q->where('usuario_id', $usuarioId))
            ->orderByDesc('data_historia')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function buscarPorTermo(string $termo, int $limite)
    {
        return Historia::with('tipo:id,nome')
            ->where(function ($q) use ($termo) {
                $like = Sanitizador::like($termo);
                $q->where(HistoriaMapping::TITULO, 'like', $like)
                    ->orWhere(HistoriaMapping::DESCRICAO_CURTA, 'like', $like);
            })
            ->orderByDesc('data_historia')
            ->limit($limite)
            ->get();
    }

    /** Contagens de midia ignorando itens excluidos (as relacoes usam withTrashed). */
    public static function contagensMidia(): array
    {
        return [
            'fotos' => fn ($q) => $q->whereNull('fotos.deleted_at'),
            'videos' => fn ($q) => $q->whereNull('videos.deleted_at'),
            'musicas' => fn ($q) => $q->whereNull('musicas.deleted_at'),
        ];
    }

    private function aplicarFiltros($query, array $filtros): void
    {
        if (!empty($filtros['q'])) {
            $like = Sanitizador::like($filtros['q']);
            $query->where(function ($q) use ($like) {
                $q->where(HistoriaMapping::TITULO, 'like', $like)
                    ->orWhere(HistoriaMapping::DESCRICAO_CURTA, 'like', $like);
            });
        }

        if (!empty($filtros['tipo'])) {
            $query->whereHas('tipo', fn ($q) => $q->where('nome', $filtros['tipo']));
        }

        if (!empty($filtros['usuario'])) {
            $query->whereHas('usuarios', fn ($q) => $q->where('usuarios.uuid', $filtros['usuario']));
        }
    }

    public function buscarPorUuid(string $uuid): Historia
    {
        return Historia::where(HistoriaMapping::UUID, $uuid)
            ->firstOrFail();
    }

    public function update(Historia $historia, array $dados): Historia
    {
        $historia->update($dados);
        return $historia;
    }
}
