<?php

namespace App\Enums;

enum TipoUsuarioEnum: string
{
    case MEMBRO_ADM = 'Membro ADM';
    case MEMBRO_RUA = 'Membro Rua';
    case REVISOR = 'Revisor';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
