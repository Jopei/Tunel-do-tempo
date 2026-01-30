<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\TipoImagemMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(TipoImagemMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(TipoImagemMapping::ID);
            $table->string(TipoImagemMapping::NOME)->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(TipoImagemMapping::MODEL_TABLE_NAME);
    }
};
