<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\TipoHistoriaMapping;
use App\Mapping\HistoriaMapping;

class TipoHistoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = TipoHistoriaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = TipoHistoriaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        TipoHistoriaMapping::NOME,
    ];

    protected $dates = [
        TipoHistoriaMapping::CREATED_AT,
        TipoHistoriaMapping::UPDATED_AT,
        TipoHistoriaMapping::DELETED_AT,
    ];

    public function historias()
    {
        return $this->hasMany(
            Historia::class,
            HistoriaMapping::TIPO_HISTORIA_ID,
            TipoHistoriaMapping::ID
        );
    }
}
