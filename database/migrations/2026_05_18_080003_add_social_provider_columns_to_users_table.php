<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'provider_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('provider_name')->nullable()->after('password');
                $table->string('provider_id')->nullable()->after('provider_name');
                $table->string('avatar')->nullable()->after('provider_id');
                $table->unique(['provider_name', 'provider_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'provider_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['provider_name', 'provider_id']);
                $table->dropColumn(['provider_name', 'provider_id', 'avatar']);
            });
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};
