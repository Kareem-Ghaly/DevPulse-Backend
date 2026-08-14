<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_firebase_token_can_be_saved_and_deleted(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $service = app(NotificationService::class);

        $service->saveFirebaseToken($user, [
            'token' => 'device-token',
            'device_type' => 'web',
            'browser' => 'Chrome',
        ]);

        $this->assertDatabaseHas('firebase_device_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'device-token'),
            'device_type' => 'web',
            'browser' => 'Chrome',
        ]);

        $service->deleteFirebaseToken($user, 'device-token');

        $this->assertDatabaseMissing('firebase_device_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'device-token'),
        ]);
    }

    public function test_database_notification_is_stored_even_when_firebase_sending_fails(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->firebaseDeviceTokens()->create(['token' => 'failing-token']);

        Firebase::shouldReceive('messaging')
            ->once()
            ->andThrow(new RuntimeException('Firebase unavailable'));

        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', ['type' => 'test_type']);

        $this->assertTrue($user->notifications()->where('data->type', 'test_type')->exists());
    }

    public function test_firebase_failure_does_not_fail_main_business_operation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->firebaseDeviceTokens()->create(['token' => 'failing-token']);

        Firebase::shouldReceive('messaging')
            ->once()
            ->andThrow(new RuntimeException('Firebase unavailable'));

        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', ['type' => 'safe_failure']);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_firebase_is_sent_to_all_tokens_for_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDE']);
        $user->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDI']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->twice();

        Firebase::shouldReceive('messaging')
            ->once()
            ->andReturn($messaging);

        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', ['type' => 'multi_token']);

        $this->assertSame(2, $user->firebaseDeviceTokens()->count());
    }

    public function test_invalid_firebase_tokens_are_removed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->firebaseDeviceTokens()->create(['token' => 'c2RrZmFzZGZhc2RmYXNkZmFzZGY6APA91bG9uZy10b2tlbi1zdHJpbmctaGVyZS1mb3ItdGVzdGluZy1wdXJwb3Nlcy1vbmx5X2FiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6MTIzNDU2Nzg5MDE']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('registration-token-not-registered'));

        Firebase::shouldReceive('messaging')
            ->once()
            ->andReturn($messaging);

        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', ['type' => 'invalid_token']);

        $this->assertSame(0, $user->firebaseDeviceTokens()->count());
    }

    public function test_send_to_users_deduplicates_recipients(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        app(NotificationService::class)->sendToUsers([$user, $user], 'Title', 'Body', ['type' => 'dedupe']);

        $this->assertSame(1, $user->notifications()->where('data->type', 'dedupe')->count());
    }

    public function test_notification_payload_contains_required_firebase_fields_as_strings(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        app(NotificationService::class)->sendToUser($user, 'Title', 'Body', [
            'type' => 'payload_check',
            'task_id' => 123,
        ]);

        $data = $user->notifications()->first()->data;

        $this->assertSame('Title', $data['title']);
        $this->assertSame('Body', $data['body']);
        $this->assertSame('payload_check', $data['type']);
        $this->assertSame((string) $user->id, $data['recipient_id']);
        $this->assertSame('123', $data['task_id']);
        $this->assertArrayHasKey('created_at', $data);
    }
}
