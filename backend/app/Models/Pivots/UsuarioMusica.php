<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\UsuarioMusicaMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\MusicaMapping;
use App\Models\Usuario;
use App\Models\Musica;

class UsuarioMusica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = UsuarioMusicaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = UsuarioMusicaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        UsuarioMusicaMapping::USUARIO_ID,
        UsuarioMusicaMapping::MUSICA_ID,
    ];

    protected $dates = [
        UsuarioMusicaMapping::CREATED_AT,
        UsuarioMusicaMapping::UPDATED_AT,
        UsuarioMusicaMapping::DELETED_AT,
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            UsuarioMusicaMapping::USUARIO_ID,
            UsuarioMapping::ID
        );
    }

    public function musica()
    {
        return $this->belongsTo(
            Musica::class,
            UsuarioMusicaMapping::MUSICA_ID,
            MusicaMapping::ID
        );
    }
}
