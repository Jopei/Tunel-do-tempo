<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Mapping\TipoUsuarioMapping;
use App\Enums\TipoUsuarioEnum;

class TipoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TipoUsuarioEnum::values() as $nome) {
            DB::table(TipoUsuarioMapping::MODEL_TABLE_NAME)->updateOrInsert(
                [TipoUsuarioMapping::NOME => $nome],
                [
                    TipoUsuarioMapping::NOME => $nome,
                    TipoUsuarioMapping::CREATED_AT => now(),
                    TipoUsuarioMapping::UPDATED_AT => now(),
                ]
            );
        }
    }
}
