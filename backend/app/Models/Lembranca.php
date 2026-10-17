<?php

namespace App\Models;

use App\Mapping\HistoriaMapping;
use App\Mapping\LembrancaMapping;
use App\Mapping\UsuarioMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lembranca extends Model
{
    use SoftDeletes;

    protected $table = LembrancaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = LembrancaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        LembrancaMapping::UUID,
        LembrancaMapping::HISTORIA_ID,
        LembrancaMapping::CONTEXTO,
        LembrancaMapping::USUARIO_ID,
        LembrancaMapping::TEXTO,
    ];

    public function historia()
    {
        return $this->belongsTo(Historia::class, LembrancaMapping::HISTORIA_ID, HistoriaMapping::ID);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, LembrancaMapping::USUARIO_ID, UsuarioMapping::ID);
    }
}
