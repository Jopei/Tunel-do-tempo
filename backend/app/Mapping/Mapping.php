<?php

namespace App\Mapping;

abstract class Mapping
{
    public static function table(): string
    {
        return static::MODEL_TABLE_NAME;
    }

    public static function primary(): string
    {
        return static::MODEL_PRIMARY_KEY ?? 'id';
    }

    public static function uuid(): ?string
    {
        return static::MODEL_UUID ?? null;
    }

    public static function columns(): array
    {
        $ref = new \ReflectionClass(static::class);
        $constants = $ref->getConstants();
        return array_filter($constants, fn($k) => !str_starts_with($k, 'MODEL_'), ARRAY_FILTER_USE_KEY);
    }
}
