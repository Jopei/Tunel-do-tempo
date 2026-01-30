<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\FotoMapping;
use App\Models\Usuario;
use App\Models\Foto;

class UsuarioFoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = UsuarioFotoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = UsuarioFotoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        UsuarioFotoMapping::USUARIO_ID,
        UsuarioFotoMapping::FOTO_ID,
    ];

    protected $dates = [
        UsuarioFotoMapping::CREATED_AT,
        UsuarioFotoMapping::UPDATED_AT,
        UsuarioFotoMapping::DELETED_AT,
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            UsuarioFotoMapping::USUARIO_ID,
            UsuarioMapping::ID
        );
    }

    public function foto()
    {
        return $this->belongsTo(
            Foto::class,
            UsuarioFotoMapping::FOTO_ID,
            FotoMapping::ID
        );
    }
}
