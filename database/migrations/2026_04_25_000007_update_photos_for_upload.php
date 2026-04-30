<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Uploaded photos do not have a picsum seed.
            $table->string('image_seed')->nullable()->change();
            $table->string('file_path', 500)->nullable()->after('image_seed');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('file_path');
            $table->string('image_seed')->default('')->nullable(false)->change();
        });
    }
};
