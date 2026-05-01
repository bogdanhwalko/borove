<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 10)->index();
            $table->unsignedBigInteger('telegram_user_id')->nullable()->index();
            $table->string('telegram_chat_id', 32);
            $table->string('code_hash');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_phone_verifications');
    }
};
