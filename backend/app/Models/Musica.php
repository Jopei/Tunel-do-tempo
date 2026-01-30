<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\MusicaMapping;
use App\Mapping\HistoriaMusicaMapping;
use App\Mapping\UsuarioMusicaMapping;

class Musica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = MusicaMapping::MODEL_TABLE_NAME;
    protected $primaryKey = MusicaMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        MusicaMapping::UUID,
        MusicaMapping::TITULO,
        MusicaMapping::PATH,
    ];

    protected $dates = [
        MusicaMapping::CREATED_AT,
        MusicaMapping::UPDATED_AT,
        MusicaMapping::DELETED_AT,
    ];

    public function historias()
    {
        return $this->belongsToMany(
            Historia::class,
            HistoriaMusicaMapping::MODEL_TABLE_NAME,
            HistoriaMusicaMapping::MUSICA_ID,
            HistoriaMusicaMapping::HISTORIA_ID
        )->withTimestamps()->withTrashed();
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            UsuarioMusicaMapping::MODEL_TABLE_NAME,
            UsuarioMusicaMapping::MUSICA_ID,
            UsuarioMusicaMapping::USUARIO_ID
        )->withTimestamps()->withTrashed();
    }
}
