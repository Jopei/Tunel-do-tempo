<?php

namespace App\Repositories;

use App\Enums\TipoHistoriaEnum;
use App\Enums\TipoImagemEnum;
use App\Mapping\FotoMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function buscarPorUuidComUsuarios(string $uuid, ?int $usuarioId = null): Historia
    {
        $query = Historia::where(HistoriaMapping::UUID, $uuid)
            ->with(self::usuariosComFoto())
            ->with('tipo:id,nome')
            ->withCount(self::contagensMidia());

        return $this->comDadosDeFeed($query, $usuarioId)->firstOrFail();
    }

    /** Eager load de usuarios com slug e foto de perfil (sem N+1). */
    public static function usuariosComFoto(): array
    {
        return [
            'usuarios' => fn ($q) => $q->select('usuarios.id', 'usuarios.uuid', 'usuarios.nome', 'usuarios.slug'),
            'usuarios.fotos' => fn ($q) => $q->whereNull('fotos.deleted_at')
                ->whereHas('tipo', fn ($t) => $t->where('nome', TipoImagemEnum::PERFIL->value)),
            'usuarios.fotos.tipo:id,nome',
        ];
    }

    /** Adiciona capa (uuid/thumbnail), favoritos_count, lembrancas_count e favoritado via subqueries. */
    public function comDadosDeFeed($query, ?int $usuarioId = null)
    {
        $capa = fn (string $coluna) => DB::table('historia_fotos')
            ->join('fotos', 'fotos.id', '=', 'historia_fotos.foto_id')
            ->whereColumn('historia_fotos.historia_id', 'historias.id')
            ->whereNull('fotos.deleted_at')
            ->orderByRaw('fotos.thumbnail_path is null')
            ->orderBy('historia_fotos.id')
            ->limit(1)
            ->select($coluna);

        return $query
            ->addSelect('historias.*')
            ->selectSub($capa('fotos.uuid'), 'capa_foto_uuid')
            ->selectSub($capa('fotos.thumbnail_path'), 'capa_thumbnail_path')
            ->withCount(['favoritos', 'lembrancas'])
            ->when($usuarioId, fn ($q) => $q->withExists([
                'favoritos as favoritado' => fn ($f) => $f->where('usuario_id', $usuarioId),
            ]));
    }

    /** Anterior/proxima na ordem cronologica asc (data nula por ultimo, desempate por id), sem Pessoal. */
    public function vizinhas(Historia $h): array
    {
        $base = fn () => Historia::query()
            ->select('id', 'uuid', 'titulo', 'data_historia')
            ->where('id', '!=', $h->id)
            ->whereDoesntHave('tipo', fn ($t) => $t->where('nome', TipoHistoriaEnum::PESSOAL->value));

        $data = $h->data_historia ? Carbon::parse($h->data_historia)->toDateString() : null;

        if ($data) {
            $anterior = $base()->where(fn ($q) => $q->where('data_historia', '<', $data)
                ->orWhere(fn ($i) => $i->where('data_historia', $data)->where('id', '<', $h->id)))
                ->orderByDesc('data_historia')->orderByDesc('id')->first();
            $proxima = $base()->where(fn ($q) => $q->where('data_historia', '>', $data)
                ->orWhereNull('data_historia')
                ->orWhere(fn ($i) => $i->where('data_historia', $data)->where('id', '>', $h->id)))
                ->orderByRaw('data_historia is null')->orderBy('data_historia')->orderBy('id')->first();
        } else {
            $anterior = $base()->where(fn ($q) => $q->whereNotNull('data_historia')
                ->orWhere(fn ($i) => $i->whereNull('data_historia')->where('id', '<', $h->id)))
                ->orderByRaw('data_historia is null desc')->orderByDesc('data_historia')->orderByDesc('id')->first();
            $proxima = $base()->whereNull('data_historia')->where('id', '>', $h->id)->orderBy('id')->first();
        }

        return [$anterior, $proxima];
    }

    public function buscarCompletaPorUuid(string $uuid, ?int $usuarioId = null): Historia
    {
        return Historia::where(HistoriaMapping::UUID, $uuid)
            ->with(self::usuariosComFoto())
            ->with([
                'tipo:id,nome',
                'fotos' => fn ($q) => $q->select('fotos.id', 'fotos.uuid', 'fotos.titulo', 'fotos.descricao', 'fotos.data_foto', 'fotos.thumbnail_path')
                    ->whereNull('fotos.deleted_at'),
                'videos' => fn ($q) => $q->select('videos.id', 'videos.uuid', 'videos.titulo', 'videos.thumbnail')
                    ->whereNull('videos.deleted_at'),
                'musicas' => fn ($q) => $q->select('musicas.id', 'musicas.uuid', 'musicas.titulo')
                    ->whereNull('musicas.deleted_at'),
            ])
            ->tap(fn ($q) => $this->comDadosDeFeed($q, $usuarioId))
            ->firstOrFail();
    }

    /**
     * Resumo com contagem de midia e filtros opcionais (q, tipo, usuario).
     * Sem $perPage devolve Collection; com $perPage devolve paginator.
     */
    public function listarResumo(array $filtros = [], ?int $perPage = null, ?int $usuarioId = null)
    {
        $query = Historia::with(self::usuariosComFoto())->with('tipo:id,nome')
            ->withCount(self::contagensMidia());

        $this->comDadosDeFeed($query, $usuarioId);
        $this->aplicarFiltros($query, $filtros);

        if (($filtros['ordem'] ?? 'desc') === 'asc') {
            $query->orderByRaw('historias.data_historia is null')->orderBy('data_historia')->orderBy('id');
        } else {
            $query->orderBy('data_historia', 'desc')->orderByDesc('id');
        }

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    public function favoritasDoUsuario(int $usuarioId, int $perPage)
    {
        $query = Historia::with(self::usuariosComFoto())->with('tipo:id,nome')
            ->withCount(self::contagensMidia());

        return $this->comDadosDeFeed($query, $usuarioId)
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
