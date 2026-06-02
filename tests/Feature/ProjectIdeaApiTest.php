<?php

namespace Tests\Feature;

use App\Models\ProjectIdea;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectIdeaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_project_idea_with_simplified_payload(): void
    {
        $owner = $this->studentUser('Owner Student');
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/project-ideas', [
            'title' => 'Student Team Finder',
            'abstract' => 'A post to find teammates.',
            'description' => 'Students can publish a project idea and find compatible teammates.',
            'team_size' => 3,
            'required_skills' => ['Laravel', 'MySQL'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.project_idea.title', 'Student Team Finder')
            ->assertJsonPath('data.project_idea.required_skills', ['Laravel', 'MySQL']);

        $projectIdea = $response->json('data.project_idea');

        foreach ($this->removedProjectIdeaFields() as $field) {
            $this->assertArrayNotHasKey($field, $projectIdea);
        }

        $this->assertDatabaseHas('project_ideas', [
            'owner_id' => $owner->id,
            'title' => 'Student Team Finder',
        ]);
    }

    public function test_required_skills_are_required_when_creating_project_idea(): void
    {
        Sanctum::actingAs($this->studentUser('Owner Student'));

        $response = $this->postJson('/api/project-ideas', [
            'title' => 'Student Team Finder',
            'abstract' => 'A post to find teammates.',
            'description' => 'Students can publish a project idea and find compatible teammates.',
            'team_size' => 3,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['required_skills']);
    }

    public function test_student_matching_returns_ordered_matches_and_excludes_owner(): void
    {
        $owner = $this->studentUser('Owner Student', ['Laravel']);
        $bestMatch = $this->studentUser('Best Match', ['laravel ', 'MYSQL', 'Vue.js']);
        $partialMatch = $this->studentUser('Partial Match', ['Laravel']);
        $noMatch = $this->studentUser('No Match', ['React']);

        $projectIdea = ProjectIdea::query()->create([
            'owner_id' => $owner->id,
            'title' => 'API Platform',
            'abstract' => 'Find teammates for an API platform.',
            'description' => 'A backend-heavy platform project.',
            'team_size' => 3,
            'required_skills' => ['Laravel', 'MySQL', 'Vue.js'],
        ]);
        $projectIdea->forceFill(['status' => 'published', 'is_public' => true])->save();

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/project-ideas/{$projectIdea->id}/matching/students");

        $response->assertOk()
            ->assertJsonPath('data.matches.0.student.id', $bestMatch->id)
            ->assertJsonPath('data.matches.0.matched_skills', ['Laravel', 'MySQL', 'Vue.js'])
            ->assertJsonPath('data.matches.0.missing_skills', [])
            ->assertJsonPath('data.matches.0.match_percentage', 100)
            ->assertJsonPath('data.matches.1.student.id', $partialMatch->id)
            ->assertJsonPath('data.matches.1.matched_skills', ['Laravel'])
            ->assertJsonPath('data.matches.1.missing_skills', ['MySQL', 'Vue.js'])
            ->assertJsonPath('data.matches.1.match_percentage', 33)
            ->assertJsonPath('data.matches.2.student.id', $noMatch->id)
            ->assertJsonPath('data.matches.2.match_percentage', 0);

        $studentIds = collect($response->json('data.matches'))
            ->pluck('student.id')
            ->all();

        $this->assertNotContains($owner->id, $studentIds);
    }

    private function studentUser(string $name, array $skills = []): User
    {
        $role = Role::findOrCreate('Student', 'web');

        $user = User::factory()->create([
            'name' => $name,
            'status' => 'active',
            'profile_completed' => true,
        ]);
        $user->assignRole($role);

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'full_name' => $name,
            'skills' => $skills,
        ]);

        return $user;
    }

    private function removedProjectIdeaFields(): array
    {
        return [
            'is_public',
            'status',
            'ai_keywords',
            'ai_summary',
            'ai_analysis_status',
            'ai_error',
            'tech_stack',
            'needed_roles',
            'domain',
        ];
    }
}
