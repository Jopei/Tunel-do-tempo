<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\HistoriaVideoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\VideoMapping;
use App\Models\Historia;
use App\Models\Video;

class HistoriaVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = HistoriaVideoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = HistoriaVideoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        HistoriaVideoMapping::HISTORIA_ID,
        HistoriaVideoMapping::VIDEO_ID,
    ];

    protected $dates = [
        HistoriaVideoMapping::CREATED_AT,
        HistoriaVideoMapping::UPDATED_AT,
        HistoriaVideoMapping::DELETED_AT,
    ];

    public function historia()
    {
        return $this->belongsTo(
            Historia::class,
            HistoriaVideoMapping::HISTORIA_ID,
            HistoriaMapping::ID
        );
    }

    public function video()
    {
        return $this->belongsTo(
            Video::class,
            HistoriaVideoMapping::VIDEO_ID,
            VideoMapping::ID
        );
    }
}
