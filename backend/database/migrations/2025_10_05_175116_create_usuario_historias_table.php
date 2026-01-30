<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\UsuarioHistoriaMapping;
use App\Mapping\UsuarioMapping;
use App\Mapping\HistoriaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(UsuarioHistoriaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(UsuarioHistoriaMapping::ID);

            $table->foreignId(UsuarioHistoriaMapping::USUARIO_ID)
                ->constrained(UsuarioMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId(UsuarioHistoriaMapping::HISTORIA_ID)
                ->constrained(HistoriaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(UsuarioHistoriaMapping::MODEL_TABLE_NAME);
    }
};
