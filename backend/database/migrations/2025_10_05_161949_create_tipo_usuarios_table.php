<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\TipoUsuarioMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(TipoUsuarioMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(TipoUsuarioMapping::ID);
            $table->string(TipoUsuarioMapping::NOME)->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(TipoUsuarioMapping::MODEL_TABLE_NAME);
    }
};
