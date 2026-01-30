<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\UsuarioVideoMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\VideoMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(UsuarioVideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(UsuarioVideoMapping::ID);

            $table->foreignId(UsuarioVideoMapping::USUARIO_ID)
                ->constrained(UsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId(UsuarioVideoMapping::VIDEO_ID)
                ->constrained(VideoMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(UsuarioVideoMapping::MODEL_TABLE_NAME);
    }
};
