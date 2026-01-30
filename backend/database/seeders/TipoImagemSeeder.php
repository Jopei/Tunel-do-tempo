<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Mapping\TipoImagemMapping;
use App\Enums\TipoImagemEnum;

class TipoImagemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TipoImagemEnum::values() as $nome) {
            DB::table(TipoImagemMapping::MODEL_TABLE_NAME)->updateOrInsert(
                [TipoImagemMapping::NOME => $nome],
                [
                    TipoImagemMapping::NOME => $nome,
                    TipoImagemMapping::CREATED_AT => now(),
                    TipoImagemMapping::UPDATED_AT => now(),
                ]
            );
        }
    }
}
