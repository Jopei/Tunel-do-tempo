<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplia chaves.codigo de 4 para 64 caracteres. Aditivo: codigos antigos
     * de 4 digitos continuam validos (comparacao em texto puro).
     */
    public function up(): void
    {
        Schema::table('chaves', function (Blueprint $table) {
            $table->string('codigo', 64)->change();
        });
    }

    public function down(): void
    {
        $excedentes = DB::table('chaves')->whereRaw('CHAR_LENGTH(codigo) > 4')->count();

        if ($excedentes > 0) {
            throw new RuntimeException(
                "Rollback abortado: {$excedentes} chave(s) em chaves.codigo excedem 4 caracteres; reduzir truncaria dados."
            );
        }

        Schema::table('chaves', function (Blueprint $table) {
            $table->string('codigo', 4)->change();
        });
    }
};
