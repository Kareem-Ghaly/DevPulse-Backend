<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('student_profiles')) {
            DB::statement('ALTER TABLE student_profiles MODIFY full_name VARCHAR(255) NULL');
        }

        if (Schema::hasTable('supervisor_profiles')) {
            DB::statement('ALTER TABLE supervisor_profiles MODIFY full_name VARCHAR(255) NULL');
        }

        if (Schema::hasTable('committee_member_profiles')) {
            DB::statement('ALTER TABLE committee_member_profiles MODIFY full_name VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        //
    }
};
