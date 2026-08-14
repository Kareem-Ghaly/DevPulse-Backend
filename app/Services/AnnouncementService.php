<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Http\Resources\AnnouncementResource;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Announcement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AnnouncementService
{
    use ApiResponse;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly NotificationService $notifications,
    ) {}

    public function index(): JsonResponse
    {
        $announcements = Announcement::with('creator')->latest()->paginate(12);

        return $this->paginatedResponse(
            AnnouncementResource::collection($announcements),
            $announcements,
            'Announcements retrieved successfully.'
        );
    }

    public function store(array $data): JsonResponse
    {
        $announcement = Announcement::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'created_by' => auth()->id(),
        ]);

        $this->notifications->sendToUsers(
            $this->users->getActiveUsersByRole(UserRole::Student->value),
            'New Announcement',
            $announcement->title,
            [
                'type' => NotificationType::AnnouncementPublished->value,
                'entity_type' => 'announcement',
                'entity_id' => $announcement->id,
                'action_url' => '/student/announcements',
            ]
        );

        return $this->successResponse(
            new AnnouncementResource($announcement),
            'Announcement published successfully.',
            201
        );
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return $this->successResponse(null, 'Announcement deleted.');
    }
}
