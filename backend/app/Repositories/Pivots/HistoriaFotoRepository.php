<?php

namespace App\Repositories\Pivots;

use App\Mapping\HistoriaFotoMapping;
use App\Models\Pivots\HistoriaFoto;

class HistoriaFotoRepository
{
    public function insert(array $data): bool
    {
        return HistoriaFoto::insert($data);
    }

    public function delete(int $historiaId): int
    {
        return HistoriaFoto::where(HistoriaFotoMapping::HISTORIA_ID, $historiaId)->delete();
    }

    public function deleteByHistoria(int $historiaId): int
    {
        return HistoriaFoto::where(
            HistoriaFotoMapping::HISTORIA_ID,
            $historiaId
        )->delete();
    }
}
