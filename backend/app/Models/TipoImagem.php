<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\TipoImagemMapping;
use App\Mapping\FotoMapping;

class TipoImagem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = TipoImagemMapping::MODEL_TABLE_NAME;
    protected $primaryKey = TipoImagemMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        TipoImagemMapping::NOME,
    ];

    protected $dates = [
        TipoImagemMapping::CREATED_AT,
        TipoImagemMapping::UPDATED_AT,
        TipoImagemMapping::DELETED_AT,
    ];

    public function fotos()
    {
        return $this->hasMany(
            Foto::class,
            FotoMapping::TIPO_IMAGEM_ID,
            TipoImagemMapping::ID
        );
    }
}
