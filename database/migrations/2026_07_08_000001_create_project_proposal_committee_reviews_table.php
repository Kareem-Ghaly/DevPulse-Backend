<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_proposal_committee_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_proposal_id')->constrained('project_proposals')->cascadeOnDelete();
            $table->foreignId('committee_member_id')->constrained('users')->cascadeOnDelete();
            $table->enum('decision', ['approved', 'rejected', 'needs_revision']);
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposal_committee_reviews');
    }
};