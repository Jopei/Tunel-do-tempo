<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\HistoriaFotoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\FotoMapping;
use App\Models\Foto;
use App\Models\Historia;

class HistoriaFoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = HistoriaFotoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = HistoriaFotoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        HistoriaFotoMapping::HISTORIA_ID,
        HistoriaFotoMapping::FOTO_ID,
    ];

    protected $dates = [
        HistoriaFotoMapping::CREATED_AT,
        HistoriaFotoMapping::UPDATED_AT,
        HistoriaFotoMapping::DELETED_AT,
    ];

    public function historia()
    {
        return $this->belongsTo(
            Historia::class,
            HistoriaFotoMapping::HISTORIA_ID,
            HistoriaMapping::ID
        );
    }

    public function foto()
    {
        return $this->belongsTo(
            Foto::class,
            HistoriaFotoMapping::FOTO_ID,
            FotoMapping::ID
        );
    }
}
