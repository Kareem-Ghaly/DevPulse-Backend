<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_notification_is_stored_even_when_firebase_sending_fails(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Role::findOrCreate('Student', 'web');
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
}