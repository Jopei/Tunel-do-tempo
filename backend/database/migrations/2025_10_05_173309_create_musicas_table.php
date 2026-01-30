<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\MusicaMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(MusicaMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(MusicaMapping::ID);
            $table->uuid(MusicaMapping::UUID)->unique();

            $table->string(MusicaMapping::TITULO);
            $table->string(MusicaMapping::PATH);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists(MusicaMapping::MODEL_TABLE_NAME);
    }
};
