<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_pastas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nome');

            $table->foreignId('pai_id')->nullable()
                ->constrained('video_pastas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('usuario_cadastrou_id')
                ->constrained('usuarios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Em MySQL, NULL em pai_id nao colide: unicidade de nome na raiz fica a cargo da aplicacao.
            $table->unique(['pai_id', 'nome'], 'video_pastas_pai_nome_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_pastas');
    }
};
