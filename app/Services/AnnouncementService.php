<?php
namespace App\Services;

use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\DatabaseFirebaseNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AnnouncementService
{
    use ApiResponse;

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

        User::whereHas('roles', fn($q) => $q->where('name', 'Student'))->chunk(100, function ($students) use ($announcement) {
            foreach ($students as $student) {
                $student->notify(new DatabaseFirebaseNotification(
                    title: 'New Announcement',
                    body: $announcement->title,
                    data: [
                        'type' => 'announcement',
                        'action_url' => '/student/announcements',
                        'entity_type' => 'announcement',
                        'entity_id' => $announcement->id,
                    ]
                ));
            }
        });

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