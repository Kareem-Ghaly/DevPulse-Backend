<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('abstract');
            $table->longText('description');
            $table->json('tech_stack')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('needed_roles')->nullable();
            $table->string('domain')->nullable();
            $table->json('ai_keywords')->nullable();
            $table->text('ai_summary')->nullable();
            $table->enum('ai_analysis_status', ['not_analyzed', 'analyzing', 'analyzed', 'failed'])->default('not_analyzed');
            $table->text('ai_error')->nullable();
            $table->unsignedInteger('team_size');
            $table->enum('status', ['draft', 'published', 'team_completed', 'closed'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ideas');
    }
};
