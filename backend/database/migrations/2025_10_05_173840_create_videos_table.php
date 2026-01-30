<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Mapping\VideoMapping;

return new class extends Migration {
    public function up()
    {
        Schema::create(VideoMapping::MODEL_TABLE_NAME, function (Blueprint $table) {
            $table->id(VideoMapping::ID);
            $table->uuid(VideoMapping::UUID)->unique();

            $table->string(VideoMapping::TITULO);
            $table->text(VideoMapping::DESCRICAO)->nullable();
            $table->string(VideoMapping::PATH);
            $table->string(VideoMapping::THUMBNAIL)->nullable();
            $table->boolean(VideoMapping::EXTERNAL_LINK)->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(VideoMapping::MODEL_TABLE_NAME);
    }
};
