<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\HistoriaMapping;
use App\Mapping\TipoHistoriaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(HistoriaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(HistoriaMapping::ID);
            $table->uuid(HistoriaMapping::UUID)->unique();

            $table->string(HistoriaMapping::TITULO);
            $table->string(HistoriaMapping::DESCRICAO_CURTA, 100)->nullable();
            $table->longText(HistoriaMapping::CONTEUDO); 
            $table->string(HistoriaMapping::DATA_HISTORIA, 7); 

            $table->foreignId(HistoriaMapping::TIPO_HISTORIA_ID)
                ->constrained(TipoHistoriaMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(HistoriaMapping::MODEL_TABLE_NAME);
    }
};
