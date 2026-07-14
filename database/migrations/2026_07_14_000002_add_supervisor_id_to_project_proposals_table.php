<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_proposals', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('project_team_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('project_proposals', 'last_updated_by')) {
                $table->foreignId('last_updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('project_proposals', 'supervisor_id')) {
                $table->foreignId('supervisor_id')
                    ->nullable()
                    ->after('supervisor')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('project_proposals', 'last_updated_by') && DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE project_proposals ADD CONSTRAINT project_proposals_last_updated_by_foreign FOREIGN KEY (last_updated_by) REFERENCES users(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                Log::info('Skipped adding project_proposals.last_updated_by foreign key', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (Schema::hasColumn('project_proposals', 'status') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'under_review', 'needs_changes', 'approved', 'rejected', 'changes_requested', 'needs_revision', 'supervisor_approved', 'supervisor_rejected', 'submitted_to_committee', 'committee_approved', 'committee_rejected', 'committee_needs_revision') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_proposals', 'last_updated_by') && DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE project_proposals ADD CONSTRAINT project_proposals_last_updated_by_foreign FOREIGN KEY (last_updated_by) REFERENCES users(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                Log::info('Skipped adding project_proposals.last_updated_by foreign key', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (Schema::hasColumn('project_proposals', 'status') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE project_proposals MODIFY status ENUM('draft', 'submitted', 'approved', 'rejected', 'changes_requested', 'needs_revision', 'supervisor_approved', 'supervisor_rejected', 'submitted_to_committee', 'committee_approved', 'committee_rejected', 'committee_needs_revision') DEFAULT 'draft'");
        }

        Schema::table('project_proposals', function (Blueprint $table): void {
            if (Schema::hasColumn('project_proposals', 'supervisor_id')) {
                $table->dropConstrainedForeignId('supervisor_id');
            }

            if (Schema::hasColumn('project_proposals', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};