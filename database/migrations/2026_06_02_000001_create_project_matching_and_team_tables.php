<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_idea_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->timestamps();

            $table->unique(['project_idea_id', 'student_id']);
        });

        Schema::create('project_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->unique(['project_idea_id', 'receiver_id']);
        });

        Schema::create('project_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['forming', 'completed'])->default('forming');
            $table->timestamps();

            $table->unique('project_idea_id');
        });

        Schema::create('project_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamps();

            $table->unique(['project_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_members');
        Schema::dropIfExists('project_teams');
        Schema::dropIfExists('project_join_requests');
        Schema::dropIfExists('project_idea_matches');
    }
};
