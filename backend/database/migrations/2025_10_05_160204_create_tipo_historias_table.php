<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\TipoHistoriaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(TipoHistoriaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(TipoHistoriaMapping::ID);
            $table->string(TipoHistoriaMapping::NOME)->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(TipoHistoriaMapping::MODEL_TABLE_NAME);
    }
};
