<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('album_fotos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('album_id')
                ->constrained('albuns')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('foto_id')
                ->constrained('fotos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['album_id', 'foto_id'], 'album_fotos_album_foto_unique');
            $table->index(['album_id', 'ordem'], 'album_fotos_album_ordem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_fotos');
    }
};
