<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\TipoUsuarioMapping;
use App\Mapping\UsuarioMapping;

class TipoUsuario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = TipoUsuarioMapping::MODEL_TABLE_NAME;
    protected $primaryKey = TipoUsuarioMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        TipoUsuarioMapping::NOME,
    ];

    protected $dates = [
        TipoUsuarioMapping::CREATED_AT,
        TipoUsuarioMapping::UPDATED_AT,
        TipoUsuarioMapping::DELETED_AT,
    ];

    public function usuarios()
    {
        return $this->hasMany(
            Usuario::class,
            UsuarioMapping::TIPO_USUARIO_ID,
            TipoUsuarioMapping::ID
        );
    }
}
