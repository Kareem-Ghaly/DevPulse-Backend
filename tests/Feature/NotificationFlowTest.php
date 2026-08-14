<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Enums\UserStatus;
use App\Models\ProjectIdea;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_team_invitation_notifications_go_to_authorized_recipients_and_not_actor(): void
    {
        $owner = $this->createStudent('Owner Student');
        $receiver = $this->createStudent('Receiver Student');
        $outsider = $this->createStudent('Outsider Student');

        $owner->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDE']);
        $receiver->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDI']);
        $outsider->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDM']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->twice();

        Firebase::shouldReceive('messaging')
            ->twice()
            ->andReturn($messaging);

        $idea = ProjectIdea::query()->create([
            'owner_id' => $owner->id,
            'title' => 'Smart Campus',
            'abstract' => 'Project idea',
            'description' => 'Project idea',
            'team_size' => 3,
            'required_skills' => ['Laravel'],
        ]);

        Sanctum::actingAs($owner);

        $sendResponse = $this->postJson("/api/project-ideas/{$idea->id}/invitations", [
            'receiver_id' => $receiver->id,
        ]);

        $sendResponse->assertCreated();
        $invitationId = $sendResponse->json('data.invitation.id');

        $this->assertSame(1, $receiver->notifications()->where('data->type', NotificationType::TeamInvitationSent->value)->count());
        $this->assertSame(0, $owner->notifications()->where('data->type', NotificationType::TeamInvitationSent->value)->count());
        $this->assertSame(0, $outsider->notifications()->count());

        Sanctum::actingAs($receiver);

        $this->postJson("/api/invitations/{$invitationId}/accept")
            ->assertOk();

        $this->assertSame(1, $owner->notifications()->where('data->type', NotificationType::TeamInvitationAccepted->value)->count());
        $this->assertSame(0, $receiver->notifications()->where('data->type', NotificationType::TeamInvitationAccepted->value)->count());
        $this->assertSame(0, $outsider->notifications()->count());

    }

    private function createStudent(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'status' => UserStatus::ACTIVE->value,
        ]);
        $user->assignRole(Role::findOrCreate('Student'));

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'full_name' => $name,
            'department' => 'Software Engineering',
            'skills' => ['Laravel'],
        ]);

        return $user->refresh();
    }
}
