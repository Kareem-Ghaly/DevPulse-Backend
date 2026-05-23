<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'profile_completed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('profile_completed')->default(false)->after('status');
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('supervisor_profiles')) {
            DB::statement('ALTER TABLE supervisor_profiles MODIFY full_name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE supervisor_profiles MODIFY academic_title VARCHAR(255) NULL');
            DB::statement('ALTER TABLE supervisor_profiles MODIFY department VARCHAR(255) NULL');
            DB::statement('ALTER TABLE supervisor_profiles MODIFY specialization VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'profile_completed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('profile_completed');
            });
        }
    }
};
