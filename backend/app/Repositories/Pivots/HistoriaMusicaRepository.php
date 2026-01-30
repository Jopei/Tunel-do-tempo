<?php

namespace App\Repositories\Pivots;

use App\Mapping\HistoriaMusicaMapping;
use App\Models\Pivots\HistoriaMusica;

class HistoriaMusicaRepository
{
    public function insert(array $data): bool
    {
        return HistoriaMusica::insert($data);
    }

     public function deleteByHistoria(int $historiaId): int
    {
        return HistoriaMusica::where(
            HistoriaMusicaMapping::HISTORIA_ID,
            $historiaId
        )->delete();
    }
}
