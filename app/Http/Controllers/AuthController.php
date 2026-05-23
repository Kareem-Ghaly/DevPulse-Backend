<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\CommitteeMemberRegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StudentRegisterRequest;
use App\Http\Requests\SupervisorRegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service) {}

    public function registerStudent(StudentRegisterRequest $request)
    {
        return $this->service->registerStudent($request->validated());
    }

    public function registerSupervisor(SupervisorRegisterRequest $request)
    {
        return $this->service->registerSupervisor($request->validated());
    }

    public function registerCommitteeMember(CommitteeMemberRegisterRequest $request)
    {
        return $this->service->registerCommitteeMember($request->validated());
    }

    public function login(LoginRequest $request)
    {
        return $this->service->login($request->validated());
    }

    public function adminLogin(AdminLoginRequest $request)
    {
        return $this->service->adminLogin($request->validated());
    }

    public function redirectToProvider(string $provider, Request $request)
    {
        return $this->service->redirectToProvider($provider, $request->query('role'));
    }

    public function handleProviderCallback(string $provider)
    {
        return $this->service->handleProviderCallback($provider);
    }

    public function logout()
    {
        return $this->service->logout();
    }

    public function me()
    {
        return $this->service->me();
    }
}
