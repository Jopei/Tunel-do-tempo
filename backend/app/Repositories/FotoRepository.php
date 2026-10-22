<?php

namespace App\Repositories;

use App\Models\Foto;
use Illuminate\Support\Str;
use App\DTO\FotoIndexDTO;
use App\Support\Sanitizador;
use App\Mapping\FotoMapping;
use App\Enums\TipoImagemEnum;
use Illuminate\Support\Facades\DB;

class FotoRepository
{
    public function create(array $data): Foto
    {
        return Foto::create([
            FotoMapping::UUID => Str::uuid(),
            FotoMapping::TITULO => $data['titulo'] ?? null,
            FotoMapping::DESCRICAO => $data['descricao'] ?? null,
            FotoMapping::PATH => $data['path'],
            FotoMapping::TIPO_IMAGEM_ID => $data['tipo_imagem_id'],
            FotoMapping::USUARIO_CADASTROU_ID => $data['usuario_cadastrou_id'] ?? null,
            FotoMapping::EXTERNAL_LINK => $data['external_link'] ?? 0,
            FotoMapping::CREATED_AT => $data['criado_em'] ?? now(),
            FotoMapping::DATA_FOTO => $data['data_foto'] ?? null,
            FotoMapping::LARGURA => $data['largura'] ?? null,
            FotoMapping::ALTURA => $data['altura'] ?? null,
            FotoMapping::THUMBNAIL_PATH => $data['thumbnail_path'] ?? null,
        ]);
    }


    public function buscarPorUuid(string $uuid): Foto
    {
        return Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();
    }

    /** Data de referencia da foto: data_foto (EXIF/manual) ou, na falta, a data de criacao. */
    private const DATA_EFETIVA = 'COALESCE(fotos.data_foto, DATE(fotos.created_at))';

    private function consultaBase(FotoIndexDTO $dto)
    {
        $query = Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1);

        if ($dto->q) {
            $like = Sanitizador::like($dto->q);
            $query->where(fn ($q) => $q->where(FotoMapping::TITULO, 'like', $like)
                ->orWhere(FotoMapping::DESCRICAO, 'like', $like));
        }

        if ($dto->ano) {
            $query->whereRaw($this->sqlAno() . ' = ?', [$dto->ano]);
        }

        if ($dto->usuarioUuid) {
            $query->whereHas('usuarios', fn ($q) => $q->where('usuarios.uuid', $dto->usuarioUuid));
        }

        if ($dto->albumUuid) {
            $query->whereHas('albuns', fn ($q) => $q->where('albuns.uuid', $dto->albumUuid));
        }

        if ($dto->de) {
            $query->whereRaw(self::DATA_EFETIVA . ' >= ?', [$dto->de]);
        }

        if ($dto->ate) {
            $query->whereRaw(self::DATA_EFETIVA . ' <= ?', [$dto->ate]);
        }

        return $query;
    }

    private function sqlAno(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'CAST(strftime(\'%Y\', ' . self::DATA_EFETIVA . ') AS INTEGER)'
            : 'YEAR(' . self::DATA_EFETIVA . ')';
    }

    /** Eager loads (so para logado): autor e pessoas marcadas, cada um com a foto de perfil mais recente. */
    public function relacoesLogado(): array
    {
        $perfil = fn ($q) => $q->whereNull('fotos.deleted_at')
            ->whereHas('tipo', fn ($t) => $t->where('nome', TipoImagemEnum::PERFIL->value));

        return [
            'cadastradoPor:id,uuid,nome,slug',
            'cadastradoPor.fotos' => $perfil,
            'cadastradoPor.fotos.tipo:id,nome',
            'usuarios:id,uuid,nome,slug',
            'usuarios.fotos' => $perfil,
            'usuarios.fotos.tipo:id,nome',
        ];
    }

    /** @return array<int, array{ano:?int,total:int,capa:?array}> total de fotos por ano (data_foto ou criacao), ano desc. */
    public function anos(): array
    {
        $ano = $this->sqlAno();
        $capas = $this->capasPorAno();

        return Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1)
            ->selectRaw("$ano as ano, COUNT(*) as total")
            ->groupByRaw($ano)
            ->orderByRaw('ano desc')
            ->get()
            ->map(fn ($l) => [
                'ano' => $l->ano !== null ? (int) $l->ano : null,
                'total' => (int) $l->total,
                'capa' => $capas[$l->ano === null ? '' : (int) $l->ano] ?? null,
            ])
            ->all();
    }

    /** Capa por ano (foto mais recente nao excluida, sem perfil), em lote: [ano|'' => {thumbnail_url,url}]. */
    private function capasPorAno(): array
    {
        $ano = $this->sqlAno();
        $ranqueadas = Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1)
            ->selectRaw('fotos.uuid, fotos.thumbnail_path, ' . $ano . ' as ano, ROW_NUMBER() OVER (PARTITION BY '
                . $ano . ' ORDER BY ' . self::DATA_EFETIVA . ' DESC, fotos.id DESC) as rn');

        $mapa = [];
        foreach (DB::query()->fromSub($ranqueadas, 'r')->where('rn', 1)->get() as $l) {
            $mapa[$l->ano === null ? '' : (int) $l->ano] = [
                'thumbnail_url' => $l->thumbnail_path ? asset('storage/' . $l->thumbnail_path) : null,
                'url' => route('api.fotos.show', $l->uuid),
            ];
        }

        return $mapa;
    }

    /** Fotos com o mesmo dia/mes de hoje em anos anteriores, ano desc (ate $limite fotos no total). */
    public function nesteDia(int $limite)
    {
        $hoje = now();
        $diaMes = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%m-%d', " . self::DATA_EFETIVA . ')'
            : "DATE_FORMAT(" . self::DATA_EFETIVA . ", '%m-%d')";

        return Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1)
            ->whereRaw("$diaMes = ?", [$hoje->format('m-d')])
            ->whereRaw($this->sqlAno() . ' < ?', [$hoje->year])
            ->with($this->relacoesLogado())
            ->selectRaw('fotos.*, ' . $this->sqlAno() . ' as ano_ref')
            ->orderByRaw(self::DATA_EFETIVA . ' desc')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /** Capa (miniatura preferida, senao original) da primeira foto nao excluida de cada historia: id => url. */
    public function capasDeHistorias(array $historiaIds): array
    {
        if (!$historiaIds) {
            return [];
        }

        return DB::table('historia_fotos')
            ->join('fotos', 'fotos.id', '=', 'historia_fotos.foto_id')
            ->whereIn('historia_fotos.historia_id', $historiaIds)
            ->whereNull('fotos.deleted_at')
            ->orderByRaw('fotos.thumbnail_path is null')
            ->orderBy('historia_fotos.id')
            ->get(['historia_fotos.historia_id', 'fotos.uuid', 'fotos.thumbnail_path'])
            ->unique('historia_id')
            ->mapWithKeys(fn ($l) => [$l->historia_id => $l->thumbnail_path
                ? asset('storage/' . $l->thumbnail_path)
                : route('api.fotos.show', $l->uuid)])
            ->all();
    }

    public function paginar(FotoIndexDTO $dto)
    {
        $query = $this->consultaBase($dto)
            ->when(request()->user('sanctum'), fn ($q) => $q->with($this->relacoesLogado()));
        $porData = $dto->agrupar === 'data';

        match ($dto->ordem) {
            'antigas' => ($porData ? $query->orderByRaw(self::DATA_EFETIVA . ' asc') : $query)
                ->orderBy(FotoMapping::CREATED_AT)->orderBy('id'),
            'titulo' => $query->orderBy(FotoMapping::TITULO)->orderByDesc('id'),
            default => ($porData ? $query->orderByRaw(self::DATA_EFETIVA . ' desc') : $query)
                ->orderByDesc(FotoMapping::CREATED_AT)->orderByDesc('id'),
        };

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /** @return array<int, array{data:string,total:int}> total de fotos por mes (YYYY-MM), mais recente primeiro. */
    public function linhaDoTempo(FotoIndexDTO $dto): array
    {
        $mes = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', " . self::DATA_EFETIVA . ')'
            : "DATE_FORMAT(" . self::DATA_EFETIVA . ", '%Y-%m')";

        return $this->consultaBase($dto)
            ->reorder()
            ->selectRaw("$mes as data, COUNT(*) as total")
            ->groupByRaw($mes)
            ->orderByRaw("$mes desc")
            ->get()
            ->map(fn ($linha) => ['data' => $linha->data, 'total' => (int) $linha->total])
            ->all();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Foto> */
    public function buscarPorUuids(array $uuids)
    {
        return Foto::whereIn(FotoMapping::UUID, $uuids)->get();
    }

    public function atualizar(Foto $foto, array $dados): Foto
    {
        $dadosFiltrados = array_filter([
            FotoMapping::TITULO => $dados['titulo'] ?? null,
            FotoMapping::DESCRICAO => $dados['descricao'] ?? null,
        ], fn($valor) => !is_null($valor));

        if (array_key_exists('data_foto', $dados)) {
            $dadosFiltrados[FotoMapping::DATA_FOTO] = $dados['data_foto'];
        }

        $foto->update($dadosFiltrados);

        return $foto->refresh();
    }

    public function excluir(Foto $foto): void
    {
        $foto->delete();
    }
}
