<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\UsuarioMapping;
use App\Mapping\TipoUsuarioMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(UsuarioMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(UsuarioMapping::ID);
            $table->uuid(UsuarioMapping::UUID)->unique();

            $table->string(UsuarioMapping::NOME);
            $table->string(UsuarioMapping::EMAIL)->unique();
            $table->string(UsuarioMapping::SENHA);

            $table->foreignId(UsuarioMapping::TIPO_USUARIO_ID)
                ->constrained(TipoUsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime(UsuarioMapping::ANIVERSARIO);
            $table->string(UsuarioMapping::TELEFONE, 20)->nullable();
            $table->text(UsuarioMapping::DESCRICAO)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(UsuarioMapping::MODEL_TABLE_NAME);
    }
};
