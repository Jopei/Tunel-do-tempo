<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\FotoMapping;
use App\Mapping\TipoImagemMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(FotoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(FotoMapping::ID);
            $table->uuid(FotoMapping::UUID)->unique();

            $table->string(FotoMapping::TITULO)->nullable();
            $table->text(FotoMapping::DESCRICAO)->nullable();
            $table->string(FotoMapping::PATH);
            $table->boolean(FotoMapping::EXTERNAL_LINK)->default(false);

            $table->foreignId(FotoMapping::TIPO_IMAGEM_ID)
                ->constrained(TipoImagemMapping::MODEL_TABLE_NAME)
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(FotoMapping::MODEL_TABLE_NAME);
    }
};
