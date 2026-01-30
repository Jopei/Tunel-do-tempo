<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Mapping\TipoHistoriaMapping;
use App\Enums\TipoHistoriaEnum;

class TipoHistoriaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TipoHistoriaEnum::values() as $nome) {
            DB::table(TipoHistoriaMapping::MODEL_TABLE_NAME)->updateOrInsert(
                [TipoHistoriaMapping::NOME => $nome],
                [
                    TipoHistoriaMapping::NOME => $nome,
                    TipoHistoriaMapping::CREATED_AT => now(),
                    TipoHistoriaMapping::UPDATED_AT => now(),
                ]
            );
        }
    }
}
