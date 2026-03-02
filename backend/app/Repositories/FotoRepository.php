<?php

namespace App\Repositories;

use App\Models\Foto;
use Illuminate\Support\Str;
use App\Mapping\FotoMapping;

class FotoRepository
{
    public function create(array $data): Foto
    {
        return Foto::create([
            FotoMapping::UUID => Str::uuid(),
            FotoMapping::TITULO => $data['titulo'],
            FotoMapping::DESCRICAO => $data['descricao'],
            FotoMapping::PATH => $data['path'],
            FotoMapping::TIPO_IMAGEM_ID => $data['tipo_imagem_id'],
            FotoMapping::USUARIO_CADASTROU_ID => $data['usuario_cadastrou_id'],
            FotoMapping::EXTERNAL_LINK => $data['external_link'] ?? 0,
        ]);
    }


    public function buscarPorUuid(string $uuid): Foto
    {
        return Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();
    }

    public function paginar(int $perPage)
    {
        return Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1)
            ->latest()
            ->paginate($perPage);
    }

    public function atualizar(Foto $foto, array $dados): Foto
    {
        $dadosFiltrados = array_filter([
            FotoMapping::TITULO => $dados['titulo'] ?? null,
            FotoMapping::DESCRICAO => $dados['descricao'] ?? null,
        ], fn($valor) => !is_null($valor));

        $foto->update($dadosFiltrados);

        return $foto->refresh();
    }

    public function excluir(Foto $foto): void
    {
        $foto->delete();
    }
}
