<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\HistoriaFotoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\FotoMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(HistoriaFotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(HistoriaFotoMapping::ID);

            // FK para historia
            $table->foreignId(HistoriaFotoMapping::HISTORIA_ID)
                ->constrained(HistoriaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // FK para foto
            $table->foreignId(HistoriaFotoMapping::FOTO_ID)
                ->constrained(FotoMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(HistoriaFotoMapping::MODEL_TABLE_NAME);
    }
};
