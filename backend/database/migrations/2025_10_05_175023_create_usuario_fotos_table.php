<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\FotoMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(UsuarioFotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(UsuarioFotoMapping::ID);

            // FK → usuarios
            $table->foreignId(UsuarioFotoMapping::USUARIO_ID)
                ->constrained(UsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // FK → fotos
            $table->foreignId(UsuarioFotoMapping::FOTO_ID)
                ->constrained(FotoMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(UsuarioFotoMapping::MODEL_TABLE_NAME);
    }
};
