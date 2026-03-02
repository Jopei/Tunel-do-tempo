<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Mapping\AtualizacaoMapping;

class AtualizacaoRealizada extends Model
{
    use SoftDeletes;

    protected $table = AtualizacaoMapping::MODEL_TABLE_NAME;

    protected $fillable = [
        AtualizacaoMapping::UUID,
        AtualizacaoMapping::TITULO,
        AtualizacaoMapping::CONTEUDO_MARKDOWN,
        AtualizacaoMapping::LINK_MUSICA,
        AtualizacaoMapping::USUARIO_CADASTRADO,
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->{AtualizacaoMapping::UUID} = (string) Str::uuid();
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, AtualizacaoMapping::USUARIO_CADASTRADO);
    }
}