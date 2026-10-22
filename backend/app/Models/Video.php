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
        VideoMapping::USUARIO_CADASTROU_ID,
        VideoMapping::THUMBNAIL,
        VideoMapping::EXTERNAL_LINK,
        VideoMapping::PASTA_ID,
        VideoMapping::DURACAO_SEGUNDOS,
        VideoMapping::TAMANHO_BYTES,
        VideoMapping::DATA_VIDEO,
    ];

    protected $casts = [
        VideoMapping::DURACAO_SEGUNDOS => 'integer',
        VideoMapping::TAMANHO_BYTES => 'integer',
        VideoMapping::DATA_VIDEO => 'date:Y-m-d',
    ];

    protected $dates = [
        VideoMapping::CREATED_AT,
        VideoMapping::UPDATED_AT,
        VideoMapping::DELETED_AT,
    ];

    public function cadastradoPor()
    {
        return $this->belongsTo(Usuario::class, 'usuario_cadastrou_id', 'id')->withTrashed();
    }

    public function pasta()
    {
        return $this->belongsTo(VideoPasta::class, VideoMapping::PASTA_ID, 'id');
    }

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
