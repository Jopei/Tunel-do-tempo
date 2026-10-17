<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lembrancas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Nulo para lembrancas da pagina de homenagem (contexto = 'homenagem').
            $table->foreignId('historia_id')->nullable()
                ->constrained('historias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('contexto', 30)->default('historia');

            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('texto');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['historia_id', 'created_at'], 'lembrancas_historia_created_idx');
            $table->index(['contexto', 'created_at'], 'lembrancas_contexto_created_idx');
            $table->index('usuario_id', 'lembrancas_usuario_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembrancas');
    }
};
