<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\HistoriaVideoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\VideoMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(HistoriaVideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(HistoriaVideoMapping::ID);

            // FK → historias
            $table->foreignId(HistoriaVideoMapping::HISTORIA_ID)
                ->constrained(HistoriaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // FK → videos
            $table->foreignId(HistoriaVideoMapping::VIDEO_ID)
                ->constrained(VideoMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(HistoriaVideoMapping::MODEL_TABLE_NAME);
    }
};
