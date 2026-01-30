<?php

namespace App\Enums;

enum TipoImagemEnum: string
{
    case PERFIL = 'Perfil';
    case TUMB = 'Tumb';
    case GALERIA = 'Galeria';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
