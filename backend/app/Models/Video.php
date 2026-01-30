<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\VideoMapping;
use App\Mapping\HistoriaVideoMapping;
use App\Mapping\UsuarioVideoMapping;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = VideoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = VideoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        VideoMapping::UUID,
        VideoMapping::TITULO,
        VideoMapping::DESCRICAO,
        VideoMapping::PATH,
        VideoMapping::THUMBNAIL,
        VideoMapping::EXTERNAL_LINK,
    ];

    protected $dates = [
        VideoMapping::CREATED_AT,
        VideoMapping::UPDATED_AT,
    ];

    public function historias()
    {
        return $this->belongsToMany(
            Historia::class,
            HistoriaVideoMapping::MODEL_TABLE_NAME,
            HistoriaVideoMapping::VIDEO_ID,
            HistoriaVideoMapping::HISTORIA_ID
        )->withTimestamps()->withTrashed();
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            UsuarioVideoMapping::MODEL_TABLE_NAME,
            UsuarioVideoMapping::VIDEO_ID,
            UsuarioVideoMapping::USUARIO_ID
        )->withTimestamps()->withTrashed();
    }
}
