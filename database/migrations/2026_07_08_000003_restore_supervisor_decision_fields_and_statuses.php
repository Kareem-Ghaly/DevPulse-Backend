<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_proposals')) {
            return;
        }

        Schema::table('project_proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_proposals', 'supervisor_notes')) {
                $table->text('supervisor_notes')->nullable()->after('status');
            }

            if (! Schema::hasColumn('project_proposals', 'supervisor_decided_at')) {
                $table->timestamp('supervisor_decided_at')->nullable()->after('supervisor_notes');
            }
        });

        if (Schema::hasColumn('project_proposals', 'status') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'approved', 'rejected', 'changes_requested', 'needs_revision', 'supervisor_approved', 'supervisor_rejected', 'submitted_to_committee', 'committee_approved', 'committee_rejected', 'committee_needs_revision') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_proposals')) {
            return;
        }

        Schema::table('project_proposals', function (Blueprint $table): void {
            if (Schema::hasColumn('project_proposals', 'supervisor_decided_at')) {
                $table->dropColumn('supervisor_decided_at');
            }

            if (Schema::hasColumn('project_proposals', 'supervisor_notes')) {
                $table->dropColumn('supervisor_notes');
            }
        });

        if (Schema::hasColumn('project_proposals', 'status') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'approved', 'rejected', 'changes_requested', 'supervisor_approved', 'submitted_to_committee', 'committee_approved', 'committee_rejected', 'committee_needs_revision') DEFAULT 'draft'");
        }
    }
};