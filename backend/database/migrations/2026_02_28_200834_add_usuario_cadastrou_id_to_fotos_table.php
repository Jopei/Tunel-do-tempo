<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Mapping\FotoMapping;
use App\Mapping\UsuarioMapping;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(FotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            // 1️⃣ cria como nullable primeiro
            $table->unsignedBigInteger(FotoMapping::USUARIO_CADASTROU_ID)
                ->nullable()
                ->after(FotoMapping::TIPO_IMAGEM_ID);

        });

        // 2️⃣ pegar um usuário válido (primeiro usuário do sistema)
        $usuarioPadrao = DB::table(UsuarioMapping::MODEL_TABLE_NAME)
            ->orderBy(UsuarioMapping::ID)
            ->first();

        if ($usuarioPadrao) {

            DB::table(FotoMapping::MODEL_TABLE_NAME)
                ->whereNull(FotoMapping::USUARIO_CADASTROU_ID)
                ->update([
                    FotoMapping::USUARIO_CADASTROU_ID => $usuarioPadrao->id
                ]);
        }

        // 3️⃣ agora tornamos NOT NULL
        Schema::table(FotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->unsignedBigInteger(FotoMapping::USUARIO_CADASTROU_ID)
                ->nullable(false)
                ->change();

        });

        // 4️⃣ agora sim cria a foreign key
        Schema::table(FotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->foreign(FotoMapping::USUARIO_CADASTROU_ID)
                ->references(UsuarioMapping::ID)
                ->on(UsuarioMapping::MODEL_TABLE_NAME)
                ->restrictOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table(FotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->dropForeign([FotoMapping::USUARIO_CADASTROU_ID]);
            $table->dropColumn(FotoMapping::USUARIO_CADASTROU_ID);

        });
    }
};