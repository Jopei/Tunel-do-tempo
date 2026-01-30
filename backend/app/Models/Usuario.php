<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\UsuarioMapping;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Mapping\TipoUsuarioMapping;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\UsuarioMusicaMapping;
use App\Mapping\UsuarioVideoMapping;
use App\Mapping\UsuarioHistoriaMapping;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $table = UsuarioMapping::MODEL_TABLE_NAME;
    protected $primaryKey = UsuarioMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        UsuarioMapping::UUID,
        UsuarioMapping::NOME,
        UsuarioMapping::EMAIL,
        UsuarioMapping::SENHA,
        UsuarioMapping::TIPO_USUARIO_ID,
        UsuarioMapping::ANIVERSARIO,
        UsuarioMapping::TELEFONE,
        UsuarioMapping::DESCRICAO,
    ];

    protected $dates = [
        UsuarioMapping::ANIVERSARIO,
        UsuarioMapping::CREATED_AT,
        UsuarioMapping::UPDATED_AT,
        UsuarioMapping::DELETED_AT,
    ];

    protected $hidden = [
        UsuarioMapping::SENHA,
    ];

    public function tipoUsuario()
    {
        return $this->belongsTo(TipoUsuario::class, 
        UsuarioMapping::TIPO_USUARIO_ID, 
        TipoUsuarioMapping::ID);
    }

    public function fotos()
    {
        return $this->belongsToMany(
            Foto::class,
            UsuarioFotoMapping::MODEL_TABLE_NAME,
            UsuarioFotoMapping::USUARIO_ID,
            UsuarioFotoMapping::FOTO_ID
        )->withTimestamps()->withTrashed();
    }

    public function musicas()
    {
        return $this->belongsToMany(
            Musica::class,
            UsuarioMusicaMapping::MODEL_TABLE_NAME,
            UsuarioMusicaMapping::USUARIO_ID,
            UsuarioMusicaMapping::MUSICA_ID
        )->withTimestamps()->withTrashed();
    }

    public function videos()
    {
        return $this->belongsToMany(
            Video::class,
            UsuarioVideoMapping::MODEL_TABLE_NAME,
            UsuarioVideoMapping::USUARIO_ID,
            UsuarioVideoMapping::VIDEO_ID
        )->withTimestamps()->withTrashed();
    }

    public function historias()
    {
        return $this->belongsToMany(
            Historia::class,
            UsuarioHistoriaMapping::MODEL_TABLE_NAME,
            UsuarioHistoriaMapping::USUARIO_ID,
            UsuarioHistoriaMapping::HISTORIA_ID
        )->withTimestamps()->withTrashed();
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function getAuthIdentifierName()
    {
        return 'email';
    }

    public function setSenhaAttribute($value): void
    {
        if (Hash::needsRehash($value)) {
            $this->attributes['senha'] = Hash::make($value);
        } else {
            $this->attributes['senha'] = $value;
        }
    }
}
