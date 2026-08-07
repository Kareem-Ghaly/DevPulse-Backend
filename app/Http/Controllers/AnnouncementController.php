<?php
namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $service) {}

    public function index()
    {
        return $this->service->index();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);
        return $this->service->store($validated);
    }

    public function destroy(Announcement $announcement)
    {
        return $this->service->destroy($announcement);
    }
}