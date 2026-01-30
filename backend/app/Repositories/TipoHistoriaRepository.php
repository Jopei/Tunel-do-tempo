<?php

namespace App\Repositories;

use App\Models\TipoHistoria;
use App\Mapping\TipoHistoriaMapping;

class TipoHistoriaRepository
{
    public function buscarIdPorNome(string $nome): int
    {
        return TipoHistoria::where(
            TipoHistoriaMapping::NOME,
            $nome
        )->value(TipoHistoriaMapping::ID);
    }
}
