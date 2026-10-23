<?php

namespace App\Models;

use App\Mapping\UsuarioMapping;
use App\Mapping\VideoMapping;
use App\Mapping\VideoPastaMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoPasta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = VideoPastaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = VideoPastaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        VideoPastaMapping::UUID,
        VideoPastaMapping::NOME,
        VideoPastaMapping::PAI_ID,
        VideoPastaMapping::USUARIO_CADASTROU_ID,
    ];

    public function pai()
    {
        return $this->belongsTo(self::class, VideoPastaMapping::PAI_ID, VideoPastaMapping::ID);
    }

    public function filhas()
    {
        return $this->hasMany(self::class, VideoPastaMapping::PAI_ID, VideoPastaMapping::ID);
    }

    public function videos()
    {
        return $this->hasMany(Video::class, VideoMapping::PASTA_ID, VideoPastaMapping::ID);
    }

    public function cadastradoPor()
    {
        return $this->belongsTo(Usuario::class, VideoPastaMapping::USUARIO_CADASTROU_ID, UsuarioMapping::ID);
    }
}
