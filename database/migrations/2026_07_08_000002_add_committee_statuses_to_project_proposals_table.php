<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_proposals') || ! Schema::hasColumn('project_proposals', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'approved', 'rejected', 'changes_requested', 'supervisor_approved', 'submitted_to_committee', 'committee_approved', 'committee_rejected', 'committee_needs_revision') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_proposals') || ! Schema::hasColumn('project_proposals', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'approved', 'rejected', 'changes_requested', 'supervisor_approved') DEFAULT 'draft'");
        }
    }
};