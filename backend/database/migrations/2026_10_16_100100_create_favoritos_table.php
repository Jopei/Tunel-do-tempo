<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('historia_id')
                ->constrained('historias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['usuario_id', 'historia_id'], 'favoritos_usuario_historia_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
