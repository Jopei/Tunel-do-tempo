<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Mapping\VideoMapping;
use App\Mapping\UsuarioMapping;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ cria como nullable primeiro
        Schema::table(VideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->unsignedBigInteger('usuario_cadastrou_id')
                ->nullable()
                ->after(VideoMapping::THUMBNAIL);

        });

        // 2️⃣ pega usuário padrão
        $usuarioPadrao = DB::table(UsuarioMapping::MODEL_TABLE_NAME)
            ->orderBy(UsuarioMapping::ID)
            ->first();

        if ($usuarioPadrao) {

            DB::table(VideoMapping::MODEL_TABLE_NAME)
                ->whereNull('usuario_cadastrou_id')
                ->update([
                    'usuario_cadastrou_id' => $usuarioPadrao->id
                ]);
        }

        // 3️⃣ torna NOT NULL
        Schema::table(VideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->unsignedBigInteger('usuario_cadastrou_id')
                ->nullable(false)
                ->change();

        });

        // 4️⃣ cria foreign key
        Schema::table(VideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->foreign('usuario_cadastrou_id')
                ->references(UsuarioMapping::ID)
                ->on(UsuarioMapping::MODEL_TABLE_NAME)
                ->restrictOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table(VideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {

            $table->dropForeign(['usuario_cadastrou_id']);
            $table->dropColumn('usuario_cadastrou_id');

        });
    }
};