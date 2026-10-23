<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Mapping\ChavesMapping;
use App\Models\Chaves;
use Illuminate\Support\Str;

class ChaveSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = [
            'Joao Pedro',
            'Joao Vitor',
            'Joao Gabriel',
            'Moises',
            'Lincoln',
            'Gabriel',
            'Ruan',
            'Igor',
            'Vitor',
            'Breno',
            'Daniel',
            'Mateus',
            'Pedro',
        ];

        // Codigos aleatorios (20 chars), nunca versionados; leia-os no banco (tabela chaves) para entregar os convites.
        foreach ($nomes as $nome) {
            if (Chaves::where(ChavesMapping::NOME, $nome)->exists()) {
                continue;
            }

            Chaves::create([
                ChavesMapping::NOME => $nome,
                ChavesMapping::CODIGO => Str::random(20),
            ]);
        }
    }
}
