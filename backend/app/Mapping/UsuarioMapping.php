<?php

namespace App\Mapping;

class UsuarioMapping extends Mapping
{
    public const MODEL_TABLE_NAME = 'usuarios';
    public const MODEL_PRIMARY_KEY = 'id';
    public const MODEL_UUID = 'uuid';

    public const ID = 'id';
    public const UUID = 'uuid';
    public const NOME = 'nome';
    public const EMAIL = 'email';
    public const SENHA = 'senha';
    public const TIPO_USUARIO_ID = 'tipo_usuario_id';
    public const ANIVERSARIO = 'aniversario';
    public const TELEFONE = 'telefone';
    public const DESCRICAO = 'descricao';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
}
