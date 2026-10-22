<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fotos', function (Blueprint $table) {
            $table->date('data_foto')->nullable()->index('fotos_data_foto_idx');
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('altura')->nullable();
            $table->string('thumbnail_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fotos', function (Blueprint $table) {
            $table->dropIndex('fotos_data_foto_idx');
            $table->dropColumn(['data_foto', 'largura', 'altura', 'thumbnail_path']);
        });
    }
};
