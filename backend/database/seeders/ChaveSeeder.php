<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Mapping\ChavesMapping;
use App\Models\Chaves;

class ChaveSeeder extends Seeder
{
    public function run(): void
    {
        $dados = [
            [ChavesMapping::NOME => 'Joao Pedro', ChavesMapping::CODIGO => '7077'],
            [ChavesMapping::NOME => 'Joao Vitor', ChavesMapping::CODIGO => '4319'],
            [ChavesMapping::NOME => 'Joao Gabriel', ChavesMapping::CODIGO => '7869'],
            [ChavesMapping::NOME => 'Moises', ChavesMapping::CODIGO => '6415'],
            [ChavesMapping::NOME => 'Lincoln', ChavesMapping::CODIGO => '8770'],
            [ChavesMapping::NOME => 'Gabriel', ChavesMapping::CODIGO => '1554'],
            [ChavesMapping::NOME => 'Ruan', ChavesMapping::CODIGO => '4206'],
            [ChavesMapping::NOME => 'Igor', ChavesMapping::CODIGO => '4487'],
            [ChavesMapping::NOME => 'Vitor', ChavesMapping::CODIGO => '7110'],
            [ChavesMapping::NOME => 'Breno', ChavesMapping::CODIGO => '7336'],
            [ChavesMapping::NOME => 'Daniel', ChavesMapping::CODIGO => '9780'],
            [ChavesMapping::NOME => 'Mateus', ChavesMapping::CODIGO => '6685'],
            [ChavesMapping::NOME => 'Pedro', ChavesMapping::CODIGO => '1311'],
        ];

        foreach ($dados as $item) {
            Chaves::create($item);
        }
    }
}
