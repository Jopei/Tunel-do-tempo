<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Mapping\ApiKeyMapping;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = ApiKeyMapping::MODEL_TABLE_NAME;
    protected $primaryKey = ApiKeyMapping::ID;
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        ApiKeyMapping::NOME,
        ApiKeyMapping::HASH,
        ApiKeyMapping::ATIVO,
        ApiKeyMapping::ULTIMO_USO_EM,
        ApiKeyMapping::ORIGEM,
    ];

    protected $casts = [
        ApiKeyMapping::ATIVO => 'boolean',
        ApiKeyMapping::ULTIMO_USO_EM => 'datetime',
    ];

    protected $dates = [
        ApiKeyMapping::CREATED_AT,
        ApiKeyMapping::UPDATED_AT,
    ];
}
