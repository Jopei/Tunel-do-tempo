<?php

namespace App\Mapping;

class HistoriaMapping extends Mapping
{
    public const MODEL_TABLE_NAME = 'historias';
    public const MODEL_PRIMARY_KEY = 'id';
    public const MODEL_UUID = 'uuid';

    public const ID = 'id';
    public const UUID = 'uuid';
    public const TITULO = 'titulo';
    public const DESCRICAO_CURTA = 'descricao_curta';
    public const CONTEUDO = 'conteudo';
    public const DATA_HISTORIA = 'data_historia';
    public const TIPO_HISTORIA_ID = 'tipo_historia_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
    
}