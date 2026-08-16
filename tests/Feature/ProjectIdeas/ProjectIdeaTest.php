<?php

namespace Tests\Feature\ProjectIdeas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectIdeaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_create_project_idea(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $student->assignRole(UserRole::Student->value);

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/project-ideas', [
            'title' => 'Smart Graduation Project Platform',
            'abstract' => 'A smart platform for managing graduation projects.',
            'description' => 'This platform helps students and supervisors manage graduation projects efficiently.',
            'team_size' => 4,
            'required_skills' => [
                'Laravel',
                'MySQL',
                'React',
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('project_ideas', [
            'owner_id' => $student->id,
            'title' => 'Smart Graduation Project Platform',
            'team_size' => 4,
            // 'status' => 'draft',
            // 'is_public' => false,
            'status' => 'published',
            'is_public' => true,
        ]);
    }
    public function test_unauthenticated_user_cannot_create_project_idea(): void
    {
        $response = $this->postJson('/api/project-ideas', [
            'title' => 'Unauthorized Project',
            'abstract' => 'This project should not be created.',
            'description' => 'Testing that unauthenticated users cannot create project ideas.',
            'team_size' => 4,
            'required_skills' => [
                'Laravel',
                'MySQL',
            ],
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('project_ideas', [
            'title' => 'Unauthorized Project',
        ]);
    }
    public function test_student_cannot_create_project_idea_with_invalid_data(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $student->assignRole(UserRole::Student->value);

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/project-ideas', [
            'title' => '',
            'abstract' => '',
            'description' => '',
            'team_size' => 0,
            'required_skills' => [],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('project_ideas', 0);
    }
    public function test_student_can_view_project_idea(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $student->assignRole(UserRole::Student->value);

        Sanctum::actingAs($student);

        $createResponse = $this->postJson('/api/project-ideas', [
            'title' => 'DevPulse Project',
            'abstract' => 'Graduation project management platform.',
            'description' => 'A platform for managing the graduation project lifecycle.',
            'team_size' => 4,
            'required_skills' => [
                'Laravel',
                'MySQL',
            ],
        ]);

        $createResponse->assertStatus(201);

        $projectIdeaId = $createResponse->json('data.id');

        $response = $this->getJson(
            "/api/project-ideas/{$projectIdeaId}"
        );
        $response->assertSuccessful();

        $response->assertJsonPath(
            'data.0.title',
            'DevPulse Project'
        );
    }
    public function test_unauthenticated_user_cannot_view_project_ideas(): void
    {
        $response = $this->getJson('/api/project-ideas');

        $response->assertStatus(401);
    }
}
