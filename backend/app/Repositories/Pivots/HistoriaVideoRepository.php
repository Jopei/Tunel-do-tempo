<?php

namespace App\Repositories\Pivots;

use App\Models\Pivots\HistoriaVideo;
use App\Mapping\HistoriaVideoMapping;

class HistoriaVideoRepository
{
    public function insert(array $data): bool
    {
        return HistoriaVideo::insert($data);
    }

    public function deleteByHistoria(int $historiaId): int
    {
        return HistoriaVideo::where(
            HistoriaVideoMapping::HISTORIA_ID,
            $historiaId
        )->delete();
    }
}
