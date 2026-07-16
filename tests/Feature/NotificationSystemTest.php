<?php

namespace Tests\Feature;

use App\Models\ProjectIdea;
use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\ProjectTeamMember;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_firebase_token(): void
    {
        $user = $this->userWithRole('Student', 'Student');
        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/firebase-token', [
            'token' => 'token-one',
            'device_type' => 'web',
            'browser' => 'Chrome',
        ])->assertOk();

        $this->assertDatabaseHas('firebase_device_tokens', [
            'user_id' => $user->id,
            'token' => 'token-one',
            'device_type' => 'web',
            'browser' => 'Chrome',
        ]);
    }

    public function test_same_user_token_combination_is_not_duplicated(): void
    {
        $user = $this->userWithRole('Student', 'Student');
        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/firebase-token', ['token' => 'same-token'])->assertOk();
        $this->postJson('/api/notifications/firebase-token', ['token' => 'same-token'])->assertOk();

        $this->assertSame(1, $user->firebaseDeviceTokens()->where('token', 'same-token')->count());
    }

    public function test_different_users_can_store_their_own_tokens(): void
    {
        $first = $this->userWithRole('Student', 'First');
        $second = $this->userWithRole('Student', 'Second');

        Sanctum::actingAs($first);
        $this->postJson('/api/notifications/firebase-token', ['token' => 'shared-token'])->assertOk();

        Sanctum::actingAs($second);
        $this->postJson('/api/notifications/firebase-token', ['token' => 'shared-token'])->assertOk();

        $this->assertDatabaseCount('firebase_device_tokens', 2);
    }

    public function test_authenticated_user_can_delete_only_their_own_token(): void
    {
        $first = $this->userWithRole('Student', 'First');
        $second = $this->userWithRole('Student', 'Second');
        $first->firebaseDeviceTokens()->create(['token' => 'owned-token']);
        $second->firebaseDeviceTokens()->create(['token' => 'owned-token']);

        Sanctum::actingAs($first);
        $this->deleteJson('/api/notifications/firebase-token', ['token' => 'owned-token'])->assertOk();

        $this->assertDatabaseMissing('firebase_device_tokens', ['user_id' => $first->id, 'token' => 'owned-token']);
        $this->assertDatabaseHas('firebase_device_tokens', ['user_id' => $second->id, 'token' => 'owned-token']);
    }

    public function test_unauthenticated_users_cannot_access_notification_endpoints(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->postJson('/api/notifications/firebase-token', ['token' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/notifications/firebase-token', ['token' => 'x'])->assertUnauthorized();
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
        $this->postJson('/api/notifications/fake-id/read')->assertUnauthorized();
    }

    public function test_user_can_list_only_their_own_notifications_and_unread_count_is_correct(): void
    {
        $first = $this->userWithRole('Student', 'First');
        $second = $this->userWithRole('Student', 'Second');
        app(NotificationService::class)->sendToUser($first, 'One', 'Body', ['type' => 'first']);
        app(NotificationService::class)->sendToUser($second, 'Two', 'Body', ['type' => 'second']);

        Sanctum::actingAs($first);
        $response = $this->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.data.type', 'first');

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = $this->userWithRole('Student', 'Student');
        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', ['type' => 'test']);
        $notification = $user->notifications()->first();

        Sanctum::actingAs($user);
        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $first = $this->userWithRole('Student', 'First');
        $second = $this->userWithRole('Student', 'Second');
        app(NotificationService::class)->sendToUser($first, 'Title', 'Body', ['type' => 'test']);
        $notification = $first->notifications()->first();

        Sanctum::actingAs($second);
        $this->postJson("/api/notifications/{$notification->id}/read")->assertNotFound();
    }

    public function test_mark_all_only_affects_authenticated_user(): void
    {
        $first = $this->userWithRole('Student', 'First');
        $second = $this->userWithRole('Student', 'Second');
        app(NotificationService::class)->sendToUser($first, 'One', 'Body', ['type' => 'first']);
        app(NotificationService::class)->sendToUser($second, 'Two', 'Body', ['type' => 'second']);

        Sanctum::actingAs($first);
        $this->postJson('/api/notifications/read-all')->assertOk();

        $this->assertSame(0, $first->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $second->fresh()->unreadNotifications()->count());
    }

    public function test_invitation_sent_creates_notification_for_invited_student(): void
    {
        [$owner, $team] = $this->studentWithTeam('Owner');
        $receiver = $this->userWithRole('Student', 'Receiver');

        Sanctum::actingAs($owner);
        $this->postJson("/api/project-ideas/{$team->project_idea_id}/invitations", [
            'receiver_id' => $receiver->id,
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $receiver->id,
        ]);
        $this->assertSame('team_invitation_sent', $receiver->notifications()->first()->data['type']);
    }

    public function test_invitation_accepted_creates_notification_for_owner(): void
    {
        [$owner, $team] = $this->studentWithTeam('Owner');
        $receiver = $this->userWithRole('Student', 'Receiver');
        $invitation = $this->sendInvitation($owner, $team->project_idea_id, $receiver);

        Sanctum::actingAs($receiver);
        $this->postJson("/api/invitations/{$invitation->id}/accept")->assertOk();

        $this->assertTrue($owner->notifications()->where('data->type', 'team_invitation_accepted')->exists());
    }

    public function test_proposal_submitted_creates_notification_for_assigned_supervisor(): void
    {
        [$student, $team] = $this->studentWithTeam('Student');
        $supervisor = $this->userWithRole('Supervisor', 'Supervisor');
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'title' => 'Proposal',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($student);
        $this->postJson("/api/project-proposals/{$proposal->id}/submit", [
            'supervisor_id' => $supervisor->id,
        ])->assertOk();

        $this->assertTrue($supervisor->notifications()->where('data->type', 'proposal_submitted_to_supervisor')->exists());
    }

    public function test_supervisor_decision_creates_notifications_for_team_members(): void
    {
        [$student, $team] = $this->studentWithTeam('Student');
        $member = $this->userWithRole('Student', 'Member');
        ProjectTeamMember::query()->create(['project_team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);
        $supervisor = $this->userWithRole('Supervisor', 'Supervisor');
        $proposal = ProjectProposal::query()->create([
            'project_team_id' => $team->id,
            'created_by' => $student->id,
            'supervisor_id' => $supervisor->id,
            'supervisor' => $supervisor->name,
            'title' => 'Proposal',
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($supervisor);
        $this->postJson("/api/project-proposals/{$proposal->id}/decision", ['status' => 'approved'])->assertOk();

        $this->assertTrue($student->notifications()->where('data->type', 'proposal_approved_by_supervisor')->exists());
        $this->assertTrue($member->notifications()->where('data->type', 'proposal_approved_by_supervisor')->exists());
    }

    public function test_task_assignment_creates_notification_for_assigned_student(): void
    {
        [$owner, $team] = $this->studentWithTeam('Owner');
        $assignee = $this->userWithRole('Student', 'Assignee');
        ProjectTeamMember::query()->create(['project_team_id' => $team->id, 'user_id' => $assignee->id, 'role' => 'member']);

        Sanctum::actingAs($owner);
        $this->postJson("/api/project-teams/{$team->id}/tasks", [
            'title' => 'Task',
            'assigned_to' => $assignee->id,
        ])->assertCreated();

        $this->assertTrue($assignee->notifications()->where('data->type', 'task_assigned')->exists());
    }

    public function test_task_status_update_does_not_create_duplicate_assignment_notification(): void
    {
        [$owner, $team] = $this->studentWithTeam('Owner');
        $assignee = $this->userWithRole('Student', 'Assignee');
        ProjectTeamMember::query()->create(['project_team_id' => $team->id, 'user_id' => $assignee->id, 'role' => 'member']);
        $task = Task::query()->create([
            'project_team_id' => $team->id,
            'title' => 'Task',
            'assigned_to' => $assignee->id,
            'created_by' => $owner->id,
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        app(NotificationService::class)->sendToUser($assignee, 'Task assigned', 'You were assigned a task.', ['type' => 'task_assigned']);

        Sanctum::actingAs($owner);
        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'todo'])->assertOk();

        $this->assertSame(1, $assignee->notifications()->where('data->type', 'task_assigned')->count());
    }

    private function userWithRole(string $roleName, string $name): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create(['name' => $name, 'status' => 'active', 'profile_completed' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function studentWithTeam(string $name): array
    {
        $student = $this->userWithRole('Student', $name);
        $idea = ProjectIdea::query()->create([
            'owner_id' => $student->id,
            'title' => $name.' Idea',
            'abstract' => 'Abstract',
            'description' => 'Description',
            'team_size' => 2,
            'required_skills' => ['Laravel'],
        ]);
        $team = ProjectTeam::query()->create([
            'project_idea_id' => $idea->id,
            'leader_id' => $student->id,
            'status' => 'forming',
        ]);
        ProjectTeamMember::query()->create(['project_team_id' => $team->id, 'user_id' => $student->id, 'role' => 'leader']);

        return [$student, $team];
    }

    private function sendInvitation(User $owner, int $projectIdeaId, User $receiver)
    {
        Sanctum::actingAs($owner);
        $this->postJson("/api/project-ideas/{$projectIdeaId}/invitations", [
            'receiver_id' => $receiver->id,
        ])->assertCreated();

        return $receiver->projectInvitationsReceived()->first();
    }
}