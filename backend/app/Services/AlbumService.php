<?php

namespace App\Services;

use App\Mapping\AlbumMapping;
use App\Models\Album;
use App\Repositories\AlbumRepository;
use Illuminate\Support\Facades\DB;

class AlbumService
{
    public function __construct(protected AlbumRepository $albuns) {}

    public function listar(?string $q, int $page, int $perPage)
    {
        return $this->albuns->paginar($q, $page, $perPage);
    }

    public function buscar(string $uuid): Album
    {
        return $this->albuns->buscarPorUuid($uuid);
    }

    public function criar(array $dados): Album
    {
        return DB::transaction(function () use ($dados) {
            $capaId = $this->resolverCapa($dados['capa_foto_uuid'] ?? null);

            $album = $this->albuns->criar([
                'titulo' => $dados['titulo'],
                'descricao' => $dados['descricao'] ?? null,
                'capa_foto_id' => $capaId,
                'usuario_cadastrou_id' => auth()->user()->id,
            ]);

            if (!empty($dados['fotos'])) {
                $this->albuns->adicionarFotos($album, array_values($this->albuns->idsFotosPorUuids($dados['fotos'])));
            }

            return $album;
        });
    }

    public function atualizar(string $uuid, array $dados, bool $informouCapa): Album
    {
        $album = $this->albuns->buscarPorUuid($uuid);
        $campos = [];

        if (array_key_exists('titulo', $dados)) {
            $campos[AlbumMapping::TITULO] = $dados['titulo'];
        }
        if (array_key_exists('descricao', $dados)) {
            $campos[AlbumMapping::DESCRICAO] = $dados['descricao'];
        }
        if ($informouCapa) {
            $campos[AlbumMapping::CAPA_FOTO_ID] = $this->resolverCapa($dados['capa_foto_uuid'] ?? null);
        }

        return $this->albuns->atualizar($album, $campos);
    }

    public function excluir(string $uuid): void
    {
        $this->albuns->excluir($this->albuns->buscarPorUuid($uuid));
    }

    public function adicionarFotos(string $uuid, array $fotosUuid): Album
    {
        $album = $this->albuns->buscarPorUuid($uuid);
        $this->albuns->adicionarFotos($album, array_values($this->albuns->idsFotosPorUuids($fotosUuid)));

        return $album;
    }

    public function removerFotos(string $uuid, array $fotosUuid): Album
    {
        $album = $this->albuns->buscarPorUuid($uuid);
        $ids = array_values($this->albuns->idsFotosPorUuids($fotosUuid));
        $this->albuns->removerFotos($album, $ids);

        if ($album->{AlbumMapping::CAPA_FOTO_ID} && in_array($album->{AlbumMapping::CAPA_FOTO_ID}, $ids, true)) {
            $album = $this->albuns->atualizar($album, [AlbumMapping::CAPA_FOTO_ID => null]);
        }

        return $album;
    }

    private function resolverCapa(?string $fotoUuid): ?int
    {
        if (!$fotoUuid) {
            return null;
        }

        return $this->albuns->idsFotosPorUuids([$fotoUuid])[$fotoUuid] ?? null;
    }
}
