<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Mapping\NotificacaoMapping;

class Notificacao extends Model
{
    use SoftDeletes;

    protected $table = NotificacaoMapping::MODEL_TABLE_NAME;
    protected $primaryKey = NotificacaoMapping::MODEL_PRIMARY_KEY;

    protected $fillable = [
        NotificacaoMapping::UUID,
        NotificacaoMapping::TITULO,
        NotificacaoMapping::DESCRICAO,
        NotificacaoMapping::TEMA,
        NotificacaoMapping::LINK,
        NotificacaoMapping::USUARIO_ID,
    ];

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'notificacao_usuario',
            'notificacao_id',
            'usuario_id'
        )->withPivot('lida_em')->withTimestamps();
    }
}