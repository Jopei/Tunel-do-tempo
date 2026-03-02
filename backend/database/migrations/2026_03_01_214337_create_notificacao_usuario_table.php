<?php

use App\Mapping\NotificacaoMapping;
use App\Mapping\NotificacaoUsuarioMapping;
use App\Mapping\UsuarioMapping;
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
        Schema::create(NotificacaoUsuarioMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(NotificacaoUsuarioMapping::USUARIO_ID);
            $table->unsignedBigInteger(NotificacaoUsuarioMapping::NOTIFICACAO_ID);

            $table->timestamp(NotificacaoUsuarioMapping::LIDA_EM)->nullable();
            $table->timestamps();

            $table->unique([
                NotificacaoUsuarioMapping::USUARIO_ID,
                NotificacaoUsuarioMapping::NOTIFICACAO_ID
            ]);

            $table->foreign(NotificacaoUsuarioMapping::USUARIO_ID)
                ->references(UsuarioMapping::ID)
                ->on(UsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnDelete();

            $table->foreign(NotificacaoUsuarioMapping::NOTIFICACAO_ID)
                ->references(NotificacaoMapping::ID)
                ->on(NotificacaoMapping::MODEL_TABLE_NAME)
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacao_usuario');
    }
};
