<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_register_successfully(): void
    {
        $response = $this->postJson('/api/auth/register/student', [
            'name' => 'Mohammed',
            'username' => 'mohammed_test',
            'email' => 'mohammed@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'full_name' => 'Mohammed Rabata',
        ]);

        $response->assertSuccessful();

        // $this->assertDatabaseHas('users', [
        //     'email' => 'mohammed@test.com',
        //     'role' => UserRole::Student->value,
        //     'status' => UserStatus::ACTIVE->value,
        // ]);
        $this->assertDatabaseHas('users', [
            'email' => 'mohammed@test.com',
            'status' => UserStatus::ACTIVE->value,
        ]);
        $user = User::where('email', 'mohammed@test.com')->first();
        $this->assertTrue($user->hasRole(UserRole::Student->value));
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->tokens);
    }
    public function test_active_student_can_login_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'student@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $user->assignRole(UserRole::Student->value);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@test.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                'token',
            ],
        ]);
    }
    public function test_student_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'student@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $user->assignRole(UserRole::Student->value);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_pending_student_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::PENDING->value,
        ]);

        $user->assignRole(UserRole::Student->value);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_rejected_student_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'rejected@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::REJECTED->value,
        ]);

        $user->assignRole(UserRole::Student->value);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'rejected@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_admin_cannot_login_through_normal_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_admin_can_login_through_admin_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        $response = $this->postJson('/api/auth/admin-login', [
            'login' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                'token',
            ],
        ]);
    }
}
