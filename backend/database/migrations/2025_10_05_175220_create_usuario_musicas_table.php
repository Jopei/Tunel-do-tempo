<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\UsuarioMusicaMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\MusicaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(UsuarioMusicaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(UsuarioMusicaMapping::ID);

            $table->foreignId(UsuarioMusicaMapping::USUARIO_ID)
                ->constrained(UsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId(UsuarioMusicaMapping::MUSICA_ID)
                ->constrained(MusicaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(UsuarioMusicaMapping::MODEL_TABLE_NAME);
    }
};
