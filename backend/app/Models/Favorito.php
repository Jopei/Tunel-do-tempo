<?php

namespace App\Models;

use App\Mapping\FavoritoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\UsuarioMapping;
use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    protected $table = FavoritoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = FavoritoMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        FavoritoMapping::USUARIO_ID,
        FavoritoMapping::HISTORIA_ID,
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, FavoritoMapping::USUARIO_ID, UsuarioMapping::ID);
    }

    public function historia()
    {
        return $this->belongsTo(Historia::class, FavoritoMapping::HISTORIA_ID, HistoriaMapping::ID);
    }
}
