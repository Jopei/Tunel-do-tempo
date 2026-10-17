<?php

namespace App\Repositories;

use App\Models\Foto;
use Illuminate\Support\Str;
use App\DTO\FotoIndexDTO;
use App\Support\Sanitizador;
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
            FotoMapping::CREATED_AT => $data['criado_em'] ?? now(),
        ]);
    }


    public function buscarPorUuid(string $uuid): Foto
    {
        return Foto::where(FotoMapping::UUID, $uuid)->firstOrFail();
    }

    public function paginar(FotoIndexDTO $dto)
    {
        $query = Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1);

        if ($dto->q) {
            $like = Sanitizador::like($dto->q);
            $query->where(fn ($q) => $q->where(FotoMapping::TITULO, 'like', $like)
                ->orWhere(FotoMapping::DESCRICAO, 'like', $like));
        }

        if ($dto->ano) {
            $query->whereYear(FotoMapping::CREATED_AT, $dto->ano);
        }

        if ($dto->usuarioUuid) {
            $query->whereHas('usuarios', fn ($q) => $q->where('usuarios.uuid', $dto->usuarioUuid));
        }

        match ($dto->ordem) {
            'antigas' => $query->orderBy(FotoMapping::CREATED_AT)->orderBy('id'),
            'titulo' => $query->orderBy(FotoMapping::TITULO)->orderByDesc('id'),
            default => $query->orderByDesc(FotoMapping::CREATED_AT)->orderByDesc('id'),
        };

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
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
