<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Usuario;
use App\Models\TipoUsuario;
use App\Mapping\UsuarioMapping;
use App\Mapping\TipoUsuarioMapping;
use App\Enums\TipoUsuarioEnum;
use Carbon\Carbon;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $tipoUsuario = TipoUsuario::where(
            TipoUsuarioMapping::NOME,
            TipoUsuarioEnum::MEMBRO_ADM->value
        )->first();

        Usuario::firstOrCreate(
            [
                UsuarioMapping::EMAIL => '',
            ],
            [
                UsuarioMapping::UUID => Str::uuid()->toString(),
                UsuarioMapping::NOME => 'João Pedro',
                UsuarioMapping::EMAIL => 'joaomoura.269@gmail.com',
                UsuarioMapping::SENHA => Hash::make('Nya@Neia2'),
                UsuarioMapping::TIPO_USUARIO_ID => $tipoUsuario->getKey(),
                UsuarioMapping::ANIVERSARIO => Carbon::create(2001, 12, 13),
                UsuarioMapping::TELEFONE => '',
                UsuarioMapping::DESCRICAO => 'Eng Comp e Dev do Site',
            ]
        );
    }
}
