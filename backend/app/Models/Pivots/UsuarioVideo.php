<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\UsuarioVideoMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\VideoMapping;
use App\Models\Usuario;
use App\Models\Video;

class UsuarioVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = UsuarioVideoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = UsuarioVideoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        UsuarioVideoMapping::USUARIO_ID,
        UsuarioVideoMapping::VIDEO_ID,
    ];

    protected $dates = [
        UsuarioVideoMapping::CREATED_AT,
        UsuarioVideoMapping::UPDATED_AT,
        UsuarioVideoMapping::DELETED_AT,
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            UsuarioVideoMapping::USUARIO_ID,
            UsuarioMapping::ID
        );
    }

    public function video()
    {
        return $this->belongsTo(
            Video::class,
            UsuarioVideoMapping::VIDEO_ID,
            VideoMapping::ID
        );
    }
}
