<?php

namespace App\Mapping;

class UsuarioMusicaMapping extends Mapping
{
    public const MODEL_TABLE_NAME = 'usuario_musicas';
    public const MODEL_PRIMARY_KEY = 'id';

    public const ID = 'id';
    public const USUARIO_ID = 'usuario_id';
    public const MUSICA_ID = 'musica_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
}