<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\UsuarioHistoriaMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\HistoriaMapping;
use App\Models\Usuario;
use App\Models\Historia;

class UsuarioHistoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = UsuarioHistoriaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = UsuarioHistoriaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        UsuarioHistoriaMapping::USUARIO_ID,
        UsuarioHistoriaMapping::HISTORIA_ID,
    ];

    protected $dates = [
        UsuarioHistoriaMapping::CREATED_AT,
        UsuarioHistoriaMapping::UPDATED_AT,
        UsuarioHistoriaMapping::DELETED_AT,
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            UsuarioHistoriaMapping::USUARIO_ID,
            UsuarioMapping::ID
        );
    }

    public function historia()
    {
        return $this->belongsTo(
            Historia::class,
            UsuarioHistoriaMapping::HISTORIA_ID,
            HistoriaMapping::ID
        );
    }
}
