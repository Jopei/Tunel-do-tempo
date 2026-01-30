<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\HistoriaMusicaMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\MusicaMapping;
use App\Models\Historia;
use App\Models\Musica;

class HistoriaMusica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = HistoriaMusicaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = HistoriaMusicaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        HistoriaMusicaMapping::HISTORIA_ID,
        HistoriaMusicaMapping::MUSICA_ID,
    ];

    protected $dates = [
        HistoriaMusicaMapping::CREATED_AT,
        HistoriaMusicaMapping::UPDATED_AT,
        HistoriaMusicaMapping::DELETED_AT,
    ];

    public function historia()
    {
        return $this->belongsTo(
            Historia::class,
            HistoriaMusicaMapping::HISTORIA_ID,
            HistoriaMapping::ID
        );
    }

    public function musica()
    {
        return $this->belongsTo(
            Musica::class,
            HistoriaMusicaMapping::MUSICA_ID,
            MusicaMapping::ID
        );
    }
}
