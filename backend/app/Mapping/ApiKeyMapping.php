<?php

namespace App\Mapping;

class ApiKeyMapping
{
    public const MODEL_TABLE_NAME = 'api_keys';

    public const ID = 'id';
    public const NOME = 'nome';
    public const HASH = 'hash';
    public const ATIVO = 'ativo';
    public const ULTIMO_USO_EM = 'ultimo_uso_em';
    public const ORIGEM = 'origem';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
}
