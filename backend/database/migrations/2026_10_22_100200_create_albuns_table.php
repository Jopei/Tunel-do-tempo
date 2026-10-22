<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('albuns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('titulo');
            $table->text('descricao')->nullable();

            $table->foreignId('capa_foto_id')->nullable()
                ->constrained('fotos')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('usuario_cadastrou_id')
                ->constrained('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albuns');
    }
};
