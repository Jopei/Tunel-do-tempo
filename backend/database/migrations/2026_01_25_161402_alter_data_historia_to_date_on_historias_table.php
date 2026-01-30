<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('historias', function (Blueprint $table) {
            $table->date('data_historia')->change();
        });
    }

    public function down(): void
    {
        Schema::table('historias', function (Blueprint $table) {
            $table->string('data_historia', 7)->change();
        });
    }
};
