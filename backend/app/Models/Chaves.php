<?php

namespace App\Models;


use App\Mapping\ChavesMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chaves extends Model
{
    use HasFactory;

    protected $table = ChavesMapping::MODEL_TABLE_NAME;

    protected $fillable = [
        ChavesMapping::NOME,
        ChavesMapping::CODIGO,
        ChavesMapping::QUEBRA,
    ];
}
