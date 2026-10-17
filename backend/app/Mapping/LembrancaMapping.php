<?php

namespace App\Mapping;

class LembrancaMapping
{
    public const MODEL_TABLE_NAME = 'lembrancas';
    public const MODEL_PRIMARY_KEY = 'id';

    public const ID = 'id';
    public const UUID = 'uuid';
    public const HISTORIA_ID = 'historia_id';
    public const CONTEXTO = 'contexto';
    public const USUARIO_ID = 'usuario_id';
    public const TEXTO = 'texto';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';

    public const CONTEXTO_HISTORIA = 'historia';
    public const CONTEXTO_HOMENAGEM = 'homenagem';
}
