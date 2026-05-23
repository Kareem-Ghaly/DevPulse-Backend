<?php

namespace App\Services;

use App\Enums\SocialProvider;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class AuthService extends BaseService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function registerStudent(array $data): JsonResponse
    {
        $user = $this->registerUserWithProfile($data, UserRole::Student, UserStatus::ACTIVE);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $user->createToken('devpulse-api-token')->plainTextToken,
        ], 'Student registered successfully', 201);
    }

    public function registerSupervisor(array $data): JsonResponse
    {
        $user = $this->registerUserWithProfile($data, UserRole::Supervisor, UserStatus::PENDING);

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Your account has been created and is waiting for admin approval.', 201);
    }

    public function registerCommitteeMember(array $data): JsonResponse
    {
        $user = $this->registerUserWithProfile($data, UserRole::CommitteeMember, UserStatus::PENDING);

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Your account has been created and is waiting for admin approval.', 201);
    }

    public function login(array $credentials): JsonResponse
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', [
                'email' => ['The provided credentials are incorrect.'],
            ], 422);
        }

        if ($user->hasRole(UserRole::Admin->value)) {
            return $this->errorResponse('Admins must use the admin login endpoint.', [
                'email' => ['Use admin login for this account.'],
            ], 403);
        }

        $statusError = $this->loginStatusError($user->status);

        if ($statusError) {
            return $this->errorResponse($statusError, [
                'status' => [$statusError],
            ], 403);
        }

        $this->users->markLastLogin($user);
        $user = $this->users->loadProfile($user);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $user->createToken('devpulse-api-token')->plainTextToken,
        ], 'Logged in successfully');
    }

    public function adminLogin(array $credentials): JsonResponse
    {
        $user = $this->users->findByUsernameOrEmail($credentials['login']);

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->hasRole(UserRole::Admin->value)) {
            return $this->errorResponse('Invalid admin credentials.', [
                'login' => ['Invalid admin credentials.'],
            ], 422);
        }

        if ($user->status !== UserStatus::ACTIVE->value) {
            return $this->errorResponse('Invalid admin credentials.', [
                'login' => ['Invalid admin credentials.'],
            ], 403);
        }

        $this->users->markLastLogin($user);
        $user = $this->users->loadProfile($user);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $user->createToken('devpulse-admin-token')->plainTextToken,
        ], 'Admin logged in successfully');
    }

    public function redirectToProvider(string $provider, ?string $role): JsonResponse
    {
        if (! SocialProvider::isSupported($provider)) {
            return $this->errorResponse('Unsupported social provider.', [
                'provider' => ['Unsupported social provider.'],
            ], 422);
        }

        if ($role !== null && ! $this->socialRoleFromSlug($role)) {
            return $this->errorResponse('Invalid social registration role.', [
                'role' => ['Only student, supervisor, and committee-member are allowed.'],
            ], 422);
        }

        $driver = Socialite::driver($provider)->stateless();

        if ($role !== null) {
            $driver->with(['state' => $role]);
        }

        return $this->successResponse([
            'redirect_url' => $driver->redirect()->getTargetUrl(),
        ], 'Redirect URL generated successfully');
    }

    public function handleProviderCallback(string $provider): JsonResponse
    {
        if (! SocialProvider::isSupported($provider)) {
            return $this->errorResponse('Unsupported social provider.', [
                'provider' => ['Unsupported social provider.'],
            ], 422);
        }

        $providerUser = Socialite::driver($provider)->stateless()->user();
        $providerId = (string) $providerUser->getId();
        $role = request()->query('state');

        $user = $this->users->findByProvider($provider, $providerId);

        if ($user) {
            return $this->socialLoginResponse($this->users->loadProfile($user), false);
        }

        $user = $this->users->findByEmail($providerUser->getEmail());

        if ($user) {
            if ($user->hasRole(UserRole::Admin->value)) {
                return $this->errorResponse('Admins must use the admin login endpoint.', [
                    'provider' => ['Admin accounts cannot use social login.'],
                ], 403);
            }

            $user = $this->users->linkProviderToUser($user, $provider, $providerId, $providerUser->getAvatar());

            return $this->socialLoginResponse($user, false);
        }

        $userRole = $this->socialRoleFromSlug($role);

        if (! $userRole) {
            return $this->errorResponse('Role is required for first social registration.', [
                'role' => ['Select student, supervisor, or committee-member before continuing.'],
            ], 422);
        }

        $user = $this->createSocialUserWithProfile($providerUser, $provider, $providerId, $userRole);

        return $this->socialLoginResponse($user, true);
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $user->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $user = $this->users->loadProfile($user);

        return $this->successResponse(new UserResource($user), 'Authenticated user retrieved successfully');
    }

    private function registerUserWithProfile(array $data, UserRole $role, UserStatus $status): User
    {
        return DB::transaction(function () use ($data, $role, $status): User {
            $profileData = $this->extractProfileData($data, $role);

            $user = $this->users->createUser([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $status->value,
                'profile_completed' => true,
            ]);

            $user->assignRole($role->value);

            match ($role) {
                UserRole::Student => $this->users->createStudentProfile($user, $profileData),
                UserRole::Supervisor => $this->users->createSupervisorProfile($user, $profileData),
                UserRole::CommitteeMember => $this->users->createCommitteeMemberProfile($user, $profileData),
                UserRole::Admin => null,
            };

            return $this->users->loadProfile($user);
        });
    }

    private function createSocialUserWithProfile(SocialiteUser $providerUser, string $provider, string $providerId, UserRole $role): User
    {
        return DB::transaction(function () use ($providerUser, $provider, $providerId, $role): User {
            $status = $role === UserRole::Student ? UserStatus::ACTIVE : UserStatus::PENDING;
            $fullName = $providerUser->getName() ?: $providerUser->getNickname() ?: 'DevPulse User';

            $user = $this->users->createSocialUser([
                'name' => $fullName,
                'email' => $providerUser->getEmail(),
                'password' => Hash::make(Str::random(48)),
                'provider_name' => $provider,
                'provider_id' => $providerId,
                'avatar' => $providerUser->getAvatar(),
                'email_verified_at' => now(),
                'status' => $status->value,
                'profile_completed' => false,
            ]);

            $user->assignRole($role->value);

            match ($role) {
                UserRole::Student => $this->users->createStudentProfile($user, [
                    'full_name' => $fullName,
                ]),
                UserRole::Supervisor => $this->users->createSupervisorProfile($user, [
                    'full_name' => $fullName,
                ]),
                UserRole::CommitteeMember => $this->users->createCommitteeMemberProfile($user, [
                    'full_name' => $fullName,
                ]),
                UserRole::Admin => null,
            };

            return $this->users->loadProfile($user);
        });
    }

    private function extractProfileData(array $data, UserRole $role): array
    {
        return match ($role) {
            UserRole::Student => [
                'full_name' => $data['full_name'],
                'university_id' => $data['university_id'] ?? null,
                'department' => $data['department'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'skills' => $data['skills'] ?? null,
                'bio' => $data['bio'] ?? null,
            ],
            UserRole::Supervisor => [
                'full_name' => $data['full_name'],
                'academic_title' => $data['academic_title'],
                'department' => $data['department'],
                'specialization' => $data['specialization'],
                'office_hours' => $data['office_hours'] ?? null,
                'bio' => $data['bio'] ?? null,
            ],
            UserRole::CommitteeMember => [
                'full_name' => $data['full_name'],
                'academic_title' => $data['academic_title'] ?? null,
                'department' => $data['department'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'bio' => $data['bio'] ?? null,
            ],
            UserRole::Admin => [],
        };
    }

    private function loginStatusError(string $status): ?string
    {
        return match ($status) {
            UserStatus::PENDING->value => 'Your account is waiting for admin approval.',
            UserStatus::REJECTED->value => 'Your account has been rejected by admin.',
            default => null,
        };
    }

    private function socialLoginResponse(User $user, bool $isNewRegistration): JsonResponse
    {
        $user = $this->users->loadProfile($user);

        if ($user->hasRole(UserRole::Admin->value)) {
            return $this->errorResponse('Admins must use the admin login endpoint.', [
                'provider' => ['Admin accounts cannot use social login.'],
            ], 403);
        }

        if ($user->status === UserStatus::PENDING->value) {
            $message = $isNewRegistration
                ? 'Your account has been created and is waiting for admin approval.'
                : 'Your account is waiting for admin approval.';

            return $this->successResponse([
                ...$this->socialResponseData($user),
            ], $message);
        }

        if ($user->status === UserStatus::REJECTED->value) {
            return $this->errorResponse('Your account has been rejected by admin.', [
                'status' => ['Your account has been rejected by admin.'],
            ], 403);
        }

        $this->users->markLastLogin($user);
        $user = $this->users->loadProfile($user);
        $token = $user->createToken('devpulse-social-token')->plainTextToken;

        return $this->successResponse([
            ...$this->socialResponseData($user, $token),
        ], 'Login successful');
    }

    private function socialResponseData(User $user, ?string $token = null): array
    {
        $data = [
            'user' => new UserResource($user),
            'role' => $user->getRoleNames()->first(),
            'status' => $user->status,
            'profile_completed' => $user->profile_completed,
        ];

        if ($token !== null) {
            $data['token'] = $token;
        }

        return $data;
    }

    private function socialRoleFromSlug(?string $role): ?UserRole
    {
        return match ($role) {
            'student' => UserRole::Student,
            'supervisor' => UserRole::Supervisor,
            'committee-member' => UserRole::CommitteeMember,
            default => null,
        };
    }
}
