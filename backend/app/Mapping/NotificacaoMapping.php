<?php

namespace App\Mapping;

class NotificacaoMapping
{
    public const MODEL_TABLE_NAME = 'notificacoes';
    public const MODEL_PRIMARY_KEY = 'id';

    public const ID = 'id';
    public const UUID = 'uuid';
    public const TITULO = 'titulo';
    public const DESCRICAO = 'descricao';
    public const TEMA = 'tema';
    public const LINK = 'link';
    public const USUARIO_ID = 'usuario_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
}