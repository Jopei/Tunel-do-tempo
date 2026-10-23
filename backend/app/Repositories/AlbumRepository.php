<?php

namespace App\Repositories;

use App\Mapping\AlbumFotoMapping;
use App\Mapping\AlbumMapping;
use App\Mapping\FotoMapping;
use App\Models\Album;
use App\Models\Foto;
use App\Support\Sanitizador;
use Illuminate\Support\Str;

class AlbumRepository
{
    public function paginar(?string $q, int $page, int $perPage)
    {
        $paginado = Album::query()
            ->with(['capa' => fn ($q) => $q->withoutTrashed()])
            ->withCount('fotos')
            ->withMin(['fotos as data_min' => fn ($f) => $f->whereNull('fotos.deleted_at')], 'fotos.data_foto')
            ->withMax(['fotos as data_max' => fn ($f) => $f->whereNull('fotos.deleted_at')], 'fotos.data_foto')
            ->when($q, function ($query) use ($q) {
                $like = Sanitizador::like($q);
                $query->where(fn ($w) => $w->where(AlbumMapping::TITULO, 'like', $like)
                    ->orWhere(AlbumMapping::DESCRICAO, 'like', $like));
            })
            ->orderByDesc(AlbumMapping::CREATED_AT)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $this->carregarPrimeirasFotos($paginado->getCollection());

        return $paginado;
    }

    /**
     * Fallback de capa (primeira foto nao excluida, por ordem) para os albuns sem capa valida,
     * em uma unica query para a pagina inteira (evita N+1 no AlbumResource).
     */
    private function carregarPrimeirasFotos($albuns): void
    {
        $semCapa = $albuns->filter(fn (Album $a) => !$a->capa);

        foreach ($semCapa as $a) {
            $a->setRelation('primeiraFoto', null);
        }

        if ($semCapa->isEmpty()) {
            return;
        }

        $pivot = AlbumFotoMapping::MODEL_TABLE_NAME;
        $fotos = Foto::query()
            ->join($pivot, $pivot . '.' . AlbumFotoMapping::FOTO_ID, '=', 'fotos.id')
            ->whereIn($pivot . '.' . AlbumFotoMapping::ALBUM_ID, $semCapa->pluck('id'))
            ->orderBy($pivot . '.' . AlbumFotoMapping::ORDEM)
            ->orderBy('fotos.id')
            ->get(['fotos.*', $pivot . '.' . AlbumFotoMapping::ALBUM_ID . ' as pivot_album_id'])
            ->unique('pivot_album_id')
            ->keyBy('pivot_album_id');

        foreach ($semCapa as $a) {
            $a->setRelation('primeiraFoto', $fotos->get($a->id));
        }
    }

    public function buscarPorUuid(string $uuid): Album
    {
        return Album::where(AlbumMapping::UUID, $uuid)->firstOrFail();
    }

    public function criar(array $dados): Album
    {
        return Album::create([
            AlbumMapping::UUID => (string) Str::uuid(),
            AlbumMapping::TITULO => $dados['titulo'],
            AlbumMapping::DESCRICAO => $dados['descricao'] ?? null,
            AlbumMapping::CAPA_FOTO_ID => $dados['capa_foto_id'] ?? null,
            AlbumMapping::USUARIO_CADASTROU_ID => $dados['usuario_cadastrou_id'],
        ]);
    }

    public function atualizar(Album $album, array $dados): Album
    {
        $album->update($dados);

        return $album;
    }

    public function excluir(Album $album): void
    {
        $album->delete();
    }

    /** @return array<int,int> ids das fotos (nao excluidas) pelos uuids */
    public function idsFotosPorUuids(array $uuids): array
    {
        return Foto::whereIn(FotoMapping::UUID, $uuids)->pluck(FotoMapping::ID, FotoMapping::UUID)->all();
    }

    public function adicionarFotos(Album $album, array $fotoIds): void
    {
        $existentes = $album->fotos()->pluck('fotos.id')->all();
        $novos = array_values(array_diff($fotoIds, $existentes));
        $proxima = (int) $album->fotos()->max(AlbumFotoMapping::MODEL_TABLE_NAME . '.' . AlbumFotoMapping::ORDEM) + 1;

        $anexos = [];
        foreach ($novos as $i => $fotoId) {
            $anexos[$fotoId] = [AlbumFotoMapping::ORDEM => $proxima + $i];
        }

        $album->fotos()->attach($anexos);
    }

    public function removerFotos(Album $album, array $fotoIds): void
    {
        $album->fotos()->detach($fotoIds);
    }
}
