<?php

namespace App\Mapping;

class UsuarioVideoMapping extends Mapping
{
    public const MODEL_TABLE_NAME = 'usuario_videos';
    public const MODEL_PRIMARY_KEY = 'id';

    public const ID = 'id';
    public const USUARIO_ID = 'usuario_id';
    public const VIDEO_ID = 'video_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
}