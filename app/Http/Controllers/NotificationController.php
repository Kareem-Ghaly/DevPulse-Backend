<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request)
    {
        return $this->notifications->listForUser($request->user());
    }

    public function markAsRead(Request $request, string $notification)
    {
        return $this->notifications->markAsRead($request->user(), $notification);
    }

    public function markAllAsRead(Request $request)
    {
        return $this->notifications->markAllAsRead($request->user());
    }
}