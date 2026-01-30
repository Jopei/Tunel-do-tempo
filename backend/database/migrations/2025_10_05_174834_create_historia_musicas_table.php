<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\HistoriaMusicaMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\MusicaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(HistoriaMusicaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(HistoriaMusicaMapping::ID);

            // FK para historias
            $table->foreignId(HistoriaMusicaMapping::HISTORIA_ID)
                ->constrained(HistoriaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // FK para musicas
            $table->foreignId(HistoriaMusicaMapping::MUSICA_ID)
                ->constrained(MusicaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(HistoriaMusicaMapping::MODEL_TABLE_NAME);
    }
};
