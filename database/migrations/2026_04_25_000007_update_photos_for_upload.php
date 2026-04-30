<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make image_seed nullable (uploaded photos won't have a picsum seed)
        DB::statement('ALTER TABLE photos MODIFY COLUMN image_seed VARCHAR(255) NULL');

        Schema::table('photos', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('image_seed');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
        DB::statement('ALTER TABLE photos MODIFY COLUMN image_seed VARCHAR(255) NOT NULL DEFAULT \'\'');
    }
};
