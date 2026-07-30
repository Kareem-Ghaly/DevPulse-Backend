<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('final_submissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_team_id')->constrained()->onDelete('cascade');
        $table->string('proposal_drive_link')->nullable();
        $table->string('presentation_drive_link')->nullable();
        $table->string('code_drive_link')->nullable();
        $table->text('student_notes')->nullable();
        $table->enum('status', ['submitted', 'graded', 'rejected'])->default('submitted');
        $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamp('graded_at')->nullable();
        
        $table->decimal('proposal_grade', 5, 2)->nullable();
        $table->text('proposal_feedback')->nullable();
        $table->decimal('presentation_grade', 5, 2)->nullable();
        $table->text('presentation_feedback')->nullable();
        $table->decimal('code_grade', 5, 2)->nullable();
        $table->text('code_feedback')->nullable();
        $table->decimal('total_grade', 5, 2)->nullable();
        
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_submissions');
    }
};
