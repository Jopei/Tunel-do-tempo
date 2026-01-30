<?php

namespace App\Enums;

enum TipoHistoriaEnum: string
{
    case PESSOAL = 'Pessoal';
    case PRINCIPAL = 'Principal';
    case PARALELA = 'Paralela';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
