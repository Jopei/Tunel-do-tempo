<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\HistoriaMapping;
use App\Mapping\TipoHistoriaMapping;
use App\Mapping\HistoriaFotoMapping;
use App\Mapping\HistoriaMusicaMapping;
use App\Mapping\HistoriaVideoMapping;
use App\Mapping\UsuarioHistoriaMapping;

class Historia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = HistoriaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = HistoriaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        HistoriaMapping::UUID,
        HistoriaMapping::TITULO,
        HistoriaMapping::DESCRICAO_CURTA,
        HistoriaMapping::CONTEUDO,
        HistoriaMapping::DATA_HISTORIA,
        HistoriaMapping::TIPO_HISTORIA_ID,
    ];

    protected $dates = [
        HistoriaMapping::CREATED_AT,
        HistoriaMapping::UPDATED_AT,
        HistoriaMapping::DELETED_AT,
    ];

    public function tipo()
    {
        return $this->belongsTo(
            TipoHistoria::class,
            HistoriaMapping::TIPO_HISTORIA_ID,
            TipoHistoriaMapping::ID
        );
    }

    public function fotos()
    {
        return $this->belongsToMany(
            Foto::class,
            HistoriaFotoMapping::MODEL_TABLE_NAME,
            HistoriaFotoMapping::HISTORIA_ID,
            HistoriaFotoMapping::FOTO_ID
        )->withTimestamps()->withTrashed();
    }

    public function musicas()
    {
        return $this->belongsToMany(
            Musica::class,
            HistoriaMusicaMapping::MODEL_TABLE_NAME,
            HistoriaMusicaMapping::HISTORIA_ID,
            HistoriaMusicaMapping::MUSICA_ID
        )->withTimestamps()->withTrashed();
    }

    public function videos()
    {
        return $this->belongsToMany(
            Video::class,
            HistoriaVideoMapping::MODEL_TABLE_NAME,
            HistoriaVideoMapping::HISTORIA_ID,
            HistoriaVideoMapping::VIDEO_ID
        )->withTimestamps()->withTrashed();
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            UsuarioHistoriaMapping::MODEL_TABLE_NAME,
            UsuarioHistoriaMapping::HISTORIA_ID,
            UsuarioHistoriaMapping::USUARIO_ID
        )->withTimestamps()->withTrashed();
    }
}
