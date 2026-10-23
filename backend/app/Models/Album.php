<?php

namespace App\Models;

use App\Mapping\AlbumFotoMapping;
use App\Mapping\AlbumMapping;
use App\Mapping\FotoMapping;
use App\Mapping\UsuarioMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Album extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = AlbumMapping::MODEL_TABLE_NAME;
    protected $primaryKey = AlbumMapping::MODEL_PRIMARY_KEY;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        AlbumMapping::UUID,
        AlbumMapping::TITULO,
        AlbumMapping::DESCRICAO,
        AlbumMapping::CAPA_FOTO_ID,
        AlbumMapping::USUARIO_CADASTROU_ID,
    ];

    public function fotos()
    {
        return $this->belongsToMany(
            Foto::class,
            AlbumFotoMapping::MODEL_TABLE_NAME,
            AlbumFotoMapping::ALBUM_ID,
            AlbumFotoMapping::FOTO_ID
        )->withPivot(AlbumFotoMapping::ORDEM)->withTimestamps()->orderBy(AlbumFotoMapping::MODEL_TABLE_NAME . '.' . AlbumFotoMapping::ORDEM);
    }

    public function capa()
    {
        return $this->belongsTo(Foto::class, AlbumMapping::CAPA_FOTO_ID, FotoMapping::ID)->withTrashed();
    }

    public function cadastradoPor()
    {
        return $this->belongsTo(Usuario::class, AlbumMapping::USUARIO_CADASTROU_ID, UsuarioMapping::ID);
    }
}
