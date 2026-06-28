<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_team_id')
                ->nullable()
                ->constrained('project_teams')
                ->nullOnDelete();

            $table->string('title');

            $table->longText('problem')->nullable();
            $table->longText('problem_overview')->nullable();
            $table->longText('comparison_table_with_similar_applications')->nullable();
            $table->longText('project_users')->nullable();

            $table->string('mind_map_problem')->nullable();

            $table->longText('solution_overview')->nullable();
            $table->longText('proposed_solution')->nullable();

            $table->string('mind_map_solution')->nullable();

            $table->longText('functional_requirements')->nullable();
            $table->longText('non_functional_requirements')->nullable();
            $table->longText('project_management')->nullable();
            $table->longText('programming_languages')->nullable();

            $table->string('supervisor')->nullable();
            $table->longText('project_teams')->nullable();

            $table->enum('status', ['draft', 'submitted'])->default('draft');

            $table->timestamp('last_update')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
    }
};