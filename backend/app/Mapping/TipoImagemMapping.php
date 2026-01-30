<?php 

namespace App\Mapping;

class TipoImagemMapping extends Mapping
{
    public const MODEL_TABLE_NAME = 'tipo_imagens';
    public const MODEL_PRIMARY_KEY = 'id';

    public const ID = 'id';
    public const NOME = 'nome';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
}