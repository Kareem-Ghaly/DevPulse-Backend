<?php

namespace Tests\Feature;

use App\Models\ProjectIdea;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\ProjectTeamMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectProposalRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_creation_stores_project_team_id_and_created_by(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $otherTeam = $this->createTeam($this->userWithRole('Student', 'Other Student'));

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/project-proposals', [
            'project_team_id' => $otherTeam->id,
            'title' => 'Team Proposal',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.proposal.project_team_id', $team->id)
            ->assertJsonPath('data.proposal.created_by', $student->id);

        $this->assertDatabaseHas('project_proposals', [
            'title' => 'Team Proposal',
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'last_updated_by' => $student->id,
        ]);
    }

    public function test_proposal_creation_without_team_returns_422(): void
    {
        $student = $this->userWithRole('Student', 'No Team Student');

        Sanctum::actingAs($student);

        $this->postJson('/api/project-proposals', [
            'title' => 'Invalid Proposal',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'You must belong to a project team before creating or submitting a proposal.');

        $this->assertDatabaseMissing('project_proposals', [
            'title' => 'Invalid Proposal',
        ]);
    }

    public function test_proposal_update_sets_last_updated_by(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'title' => 'Original Proposal',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($student);

        $this->patchJson("/api/project-proposals/{$proposal->id}", [
            'title' => 'Updated Proposal',
        ])->assertOk()
            ->assertJsonPath('data.proposal.last_updated_by', $student->id);

        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'title' => 'Updated Proposal',
            'last_updated_by' => $student->id,
        ]);
    }

    public function test_submit_to_supervisor_stores_supervisor_id(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $supervisor = $this->userWithRole('Supervisor', 'Assigned Supervisor');
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'title' => 'Submitted Proposal',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($student);

        $response = $this->postJson("/api/project-proposals/{$proposal->id}/submit", [
            'supervisor_id' => $supervisor->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.proposal.status', 'submitted')
            ->assertJsonPath('data.proposal.supervisor_id', $supervisor->id);

        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'project_team_id' => $team->id,
            'supervisor_id' => $supervisor->id,
            'supervisor' => $supervisor->name,
        ]);
    }

    public function test_legacy_numeric_supervisor_maps_to_supervisor_id_on_submit(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $supervisor = $this->userWithRole('Supervisor', 'Legacy Supervisor');
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'title' => 'Legacy Submit Proposal',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($student);

        $this->postJson("/api/project-proposals/{$proposal->id}/submit", [
            'supervisor' => (string) $supervisor->id,
        ])->assertOk()
            ->assertJsonPath('data.proposal.supervisor_id', $supervisor->id);

        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'supervisor_id' => $supervisor->id,
            'supervisor' => $supervisor->name,
        ]);
    }

    public function test_supervisor_decision_fails_with_422_if_project_team_cannot_be_resolved(): void
    {
        $supervisor = $this->userWithRole('Supervisor', 'Assigned Supervisor');
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => null,
            'created_by' => null,
            'last_updated_by' => null,
            'supervisor_id' => $supervisor->id,
            'supervisor' => $supervisor->name,
            'title' => 'Broken Proposal',
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($supervisor);

        $this->postJson("/api/project-proposals/{$proposal->id}/decision", [
            'status' => 'approved',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'This proposal is not linked to a project team and cannot be reviewed.');
    }

    public function test_assigned_supervisor_can_view_team_tasks(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $supervisor = $this->userWithRole('Supervisor', 'Assigned Supervisor');
        $task = $this->taskForTeam($team, $student);
        $this->submittedProposal($team, $supervisor, $student);

        Sanctum::actingAs($supervisor);

        $response = $this->getJson("/api/supervisor/project-teams/{$team->id}/tasks");

        $response->assertOk()
            ->assertJsonPath('data.tasks.backlog.0.id', $task->id);
    }

    public function test_unrelated_supervisor_gets_403_for_team_tasks(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $assignedSupervisor = $this->userWithRole('Supervisor', 'Assigned Supervisor');
        $unrelatedSupervisor = $this->userWithRole('Supervisor', 'Unrelated Supervisor');
        $this->taskForTeam($team, $student);
        $this->submittedProposal($team, $assignedSupervisor, $student);

        Sanctum::actingAs($unrelatedSupervisor);

        $this->getJson("/api/supervisor/project-teams/{$team->id}/tasks")
            ->assertForbidden()
            ->assertJsonPath('message', 'Access denied. You are not assigned to this project team.');
    }

    public function test_assigned_supervisor_can_review_task(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $supervisor = $this->userWithRole('Supervisor', 'Assigned Supervisor');
        $task = $this->taskForTeam($team, $student);
        $this->submittedProposal($team, $supervisor, $student);

        Sanctum::actingAs($supervisor);

        $response = $this->postJson("/api/supervisor/tasks/{$task->id}/review", [
            'review' => 'Looks good. Continue with implementation.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.review.supervisor.id', $supervisor->id);

        $this->assertDatabaseHas('task_reviews', [
            'task_id' => $task->id,
            'supervisor_id' => $supervisor->id,
            'review' => 'Looks good. Continue with implementation.',
        ]);
    }

    public function test_backfill_links_proposal_using_last_updated_by_users_unique_team(): void
    {
        [$student, $team] = $this->studentWithTeam();
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => null,
            'created_by' => null,
            'last_updated_by' => $student->id,
            'title' => 'Backfill Team Proposal',
            'status' => 'draft',
        ]);

        Artisan::call('project-proposals:backfill-relations');

        $this->assertStringContainsString("proposal {$proposal->id}: project_team_id={$team->id}", Artisan::output());
        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'project_team_id' => $team->id,
        ]);
    }

    public function test_backfill_skips_proposal_with_no_owner_or_team_evidence(): void
    {
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => null,
            'created_by' => null,
            'last_updated_by' => null,
            'title' => 'Unmatched Proposal',
            'status' => 'draft',
        ]);

        Artisan::call('project-proposals:backfill-relations');

        $this->assertStringContainsString("proposal {$proposal->id} project_team_id: no created_by or last_updated_by user with exactly one project team", Artisan::output());
        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'project_team_id' => null,
        ]);
    }

    private function userWithRole(string $roleName, string $name): User
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create([
            'name' => $name,
            'status' => 'active',
            'profile_completed' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function studentWithTeam(): array
    {
        $student = $this->userWithRole('Student', 'Team Student');
        $team = $this->createTeam($student);

        ProjectTeamMember::query()->create([
            'project_team_id' => $team->id,
            'user_id' => $student->id,
            'role' => 'leader',
        ]);

        return [$student, $team];
    }

    private function createTeam(User $leader): ProjectTeam
    {
        $idea = ProjectIdea::query()->create([
            'owner_id' => $leader->id,
            'title' => $leader->name.' Idea',
            'abstract' => 'A project idea.',
            'description' => 'A detailed project idea.',
            'team_size' => 3,
            'required_skills' => ['Laravel'],
        ]);

        return ProjectTeam::query()->create([
            'project_idea_id' => $idea->id,
            'leader_id' => $leader->id,
            'status' => 'completed',
        ]);
    }

    private function submittedProposal(ProjectTeam $team, User $supervisor, User $student): ProjectProposal
    {
        return ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'last_updated_by' => $student->id,
            'supervisor_id' => $supervisor->id,
            'supervisor' => $supervisor->name,
            'title' => 'Submitted Proposal',
            'status' => 'submitted',
        ]);
    }

    private function taskForTeam(ProjectTeam $team, User $creator): Task
    {
        return Task::query()->create([
            'project_team_id' => $team->id,
            'title' => 'Build API',
            'description' => 'Implement the API.',
            'status' => 'backlog',
            'priority' => 'medium',
            'created_by' => $creator->id,
            'last_update' => now(),
        ]);
    }
}