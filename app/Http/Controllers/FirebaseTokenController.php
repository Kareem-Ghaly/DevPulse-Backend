<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFirebaseTokenRequest;
use App\Http\Requests\StoreFirebaseTokenRequest;
use App\Services\NotificationService;

class FirebaseTokenController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function store(StoreFirebaseTokenRequest $request)
    {
        return $this->notifications->saveFirebaseToken($request->user(), $request->validated());
    }

    public function destroy(DeleteFirebaseTokenRequest $request)
    {
        return $this->notifications->deleteFirebaseToken($request->user(), $request->validated('token'));
    }
}