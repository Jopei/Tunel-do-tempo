<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('musicas', function (Blueprint $table) {
            $table->string('artista')->nullable();
            $table->string('album')->nullable();
            $table->unsignedInteger('duracao_segundos')->nullable();
            $table->foreignId('capa_foto_id')->nullable()
                ->constrained('fotos')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedSmallInteger('ano')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('musicas', function (Blueprint $table) {
            $table->dropForeign(['capa_foto_id']);
            $table->dropColumn(['artista', 'album', 'duracao_segundos', 'capa_foto_id', 'ano']);
        });
    }
};
