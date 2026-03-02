<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('atualizacoes_realizadas', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('titulo');
            $table->longText('conteudo_markdown');
            $table->string('link_musica')->nullable();
            $table->foreignId('usuario_cadastrado')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atualizacoes_realizadas');
    }
};
