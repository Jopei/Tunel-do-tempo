<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\FotoMapping;
use App\Mapping\TipoImagemMapping;
use App\Mapping\HistoriaFotoMapping;
use App\Mapping\UsuarioFotoMapping;

class Foto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = FotoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = FotoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        FotoMapping::UUID,
        FotoMapping::TITULO,
        FotoMapping::DESCRICAO,
        FotoMapping::PATH,
        FotoMapping::TIPO_IMAGEM_ID,
        FotoMapping::EXTERNAL_LINK,
    ];

    protected $dates = [
        FotoMapping::CREATED_AT,
        FotoMapping::UPDATED_AT,
        FotoMapping::DELETED_AT,
    ];

    public function tipo()
    {
        return $this->belongsTo(
            TipoImagem::class,
            FotoMapping::TIPO_IMAGEM_ID,
            TipoImagemMapping::ID
        );
    }

    public function historias()
    {
        return $this->belongsToMany(
            Historia::class,
            HistoriaFotoMapping::MODEL_TABLE_NAME,
            HistoriaFotoMapping::FOTO_ID,
            HistoriaFotoMapping::HISTORIA_ID
        )->withTimestamps()->withTrashed();
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            UsuarioFotoMapping::MODEL_TABLE_NAME,
            UsuarioFotoMapping::FOTO_ID,
            UsuarioFotoMapping::USUARIO_ID
        )->withTimestamps()->withTrashed();
    }
}
