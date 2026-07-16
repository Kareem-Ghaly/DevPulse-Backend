<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firebase_device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('token');
            $table->string('token_hash', 64);
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token_hash'], 'firebase_device_tokens_user_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firebase_device_tokens');
    }
};