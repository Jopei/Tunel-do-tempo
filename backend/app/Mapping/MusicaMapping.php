<?php

namespace App\Mapping;

class MusicaMapping
{
    public const MODEL_TABLE_NAME = 'musicas';
    public const MODEL_PRIMARY_KEY = 'id';
    public const MODEL_UUID = 'uuid';

    public const ID = 'id';
    public const UUID = 'uuid';
    public const TITULO = 'titulo';
    public const PATH = 'path';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';

    public const ARTISTA = 'artista';
    public const ALBUM = 'album';
    public const DURACAO_SEGUNDOS = 'duracao_segundos';
    public const CAPA_FOTO_ID = 'capa_foto_id';
    public const ANO = 'ano';
}
