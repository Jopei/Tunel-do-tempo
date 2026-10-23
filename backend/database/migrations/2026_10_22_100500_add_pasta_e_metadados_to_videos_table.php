<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('pasta_id')->nullable()
                ->constrained('video_pastas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedInteger('duracao_segundos')->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['pasta_id']);
            $table->dropColumn(['pasta_id', 'duracao_segundos', 'tamanho_bytes']);
        });
    }
};
