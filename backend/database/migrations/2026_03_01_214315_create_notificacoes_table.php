<?php

use App\Mapping\NotificacaoMapping;
use App\Mapping\UsuarioMapping;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(NotificacaoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string(NotificacaoMapping::TITULO);
            $table->text(NotificacaoMapping::DESCRICAO)->nullable();
            $table->string(NotificacaoMapping::TEMA)->nullable();
            $table->string(NotificacaoMapping::LINK)->nullable();
            $table->unsignedBigInteger(NotificacaoMapping::USUARIO_ID)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(NotificacaoMapping::USUARIO_ID)
                ->references(UsuarioMapping::ID)
                ->on(UsuarioMapping::MODEL_TABLE_NAME)
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
