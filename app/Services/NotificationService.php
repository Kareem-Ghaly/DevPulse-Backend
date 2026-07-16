<?php

namespace App\Services;

use App\Models\FirebaseDeviceToken;
use App\Models\User;
use App\Notifications\DatabaseFirebaseNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class NotificationService
{
    use ApiResponse;

    private const DATA_KEYS = ['title', 'body', 'type', 'entity_type', 'entity_id', 'action_url'];

    public function saveFirebaseToken(User $user, array $data): JsonResponse
    {
        $tokenHash = hash('sha256', $data['token']);

        $token = FirebaseDeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
            ],
            [
                'token' => $data['token'],
                'device_type' => $data['device_type'] ?? null,
                'browser' => $data['browser'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return $this->successResponse([
            'firebase_token' => [
                'id' => $token->id,
                'device_type' => $token->device_type,
                'browser' => $token->browser,
                'last_used_at' => $token->last_used_at,
            ],
        ], 'Firebase token saved successfully.');
    }

    public function deleteFirebaseToken(User $user, string $token): JsonResponse
    {
        FirebaseDeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->delete();

        return $this->successResponse(null, 'Firebase token deleted successfully.');
    }

    public function listForUser(User $user): JsonResponse
    {
        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => collect($notifications->items())
                ->map(fn (DatabaseNotification $notification): array => $this->notificationPayload($notification))
                ->values(),
            'unread_count' => $user->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(User $user, string $notificationId): JsonResponse
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return $this->errorResponse('Notification not found.', null, 404);
        }

        $notification->markAsRead();

        return $this->successResponse([
            'notification' => $this->notificationPayload($notification->fresh()),
            'unread_count' => $user->unreadNotifications()->count(),
        ], 'Notification marked as read.');
    }

    public function markAllAsRead(User $user): JsonResponse
    {
        $user->unreadNotifications->markAsRead();

        return $this->successResponse([
            'unread_count' => 0,
        ], 'All notifications marked as read.');
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $data = $this->normalizeData($title, $body, $data);

        $user->notify(new DatabaseFirebaseNotification($title, $body, $data));

        $this->sendFirebasePush($user, $title, $body, $data);
    }

    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): void
    {
        $seen = [];

        foreach ($users as $user) {
            if (! $user instanceof User || isset($seen[$user->id])) {
                continue;
            }

            $seen[$user->id] = true;
            $this->sendToUser($user, $title, $body, $data);
        }
    }

    private function sendFirebasePush(User $user, string $title, string $body, array $data): void
    {
        $tokens = $user->firebaseDeviceTokens()->get();

        if ($tokens->isEmpty()) {
            return;
        }

        try {
            $messaging = $this->messaging();
        } catch (Throwable $e) {
            $this->logFirebaseFailure('Firebase messaging initialization failed', $user->id, $e);

            return;
        }

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token->token)
                    ->withNotification(FirebaseNotification::create($title, $body))
                    ->withData($data);

                $messaging->send($message);
                $token->forceFill(['last_used_at' => now()])->save();
            } catch (Throwable $e) {
                if ($this->isInvalidFirebaseTokenException($e)) {
                    $token->delete();
                }

                $this->logFirebaseFailure('Firebase notification failed', $user->id, $e);
            }
        }
    }

    protected function messaging(): Messaging
    {
        return Firebase::messaging();
    }

    private function logFirebaseFailure(string $message, int $userId, Throwable $e): void
    {
        try {
            Log::warning($message, [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable) {
            // Logging must never break the business action.
        }
    }
    private function normalizeData(string $title, string $body, array $data): array
    {
        $data = [
            'title' => $title,
            'body' => $body,
            ...array_intersect_key($data, array_flip(self::DATA_KEYS)),
        ];

        return collect($data)
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private function isInvalidFirebaseTokenException(Throwable $e): bool
    {
        if (class_exists('Kreait\\Firebase\\Exception\\Messaging\\InvalidMessage') && $e instanceof \Kreait\Firebase\Exception\Messaging\InvalidMessage) {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'registration-token-not-registered')
            || str_contains($message, 'unregistered')
            || str_contains($message, 'invalid registration token')
            || str_contains($message, 'not a valid fcm registration token');
    }

    private function notificationPayload(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }

}