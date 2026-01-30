<?php

namespace App\Repositories;

use App\Mapping\FotoMapping;
use App\Models\Historia;
use App\Mapping\HistoriaMapping;
use Illuminate\Support\Str;

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

    public function buscarCompletaPorUuid(string $uuid): Historia
    {
        return Historia::where(HistoriaMapping::UUID, $uuid)
            ->with([
                'tipo:id,nome',
                'usuarios:id,uuid,nome',
                'fotos:id,uuid',
                'videos:id,uuid',
                'musicas:id,uuid',
            ])
            ->firstOrFail();
    }

    public function listarResumo()
    {
        return Historia::with(
            'usuarios:id,uuid,nome',
            'tipo:id,nome'
        )
            ->orderBy('data_historia', 'desc')
            ->get();
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
