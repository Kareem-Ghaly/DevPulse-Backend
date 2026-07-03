<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            // نتحقق أولاً لحمايتك من أي تعارض مستقبلي
            if (!Schema::hasColumn('project_proposals', 'last_updated_by')) {
                $table->unsignedBigInteger('last_updated_by')->nullable()->after('supervisor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            if (Schema::hasColumn('project_proposals', 'last_updated_by')) {
                $table->dropColumn('last_updated_by');
            }
        });
    }
};