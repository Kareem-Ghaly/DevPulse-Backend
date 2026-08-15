<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_approve_pending_supervisor(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        Sanctum::actingAs($admin);

        $supervisor = User::factory()->create([
            'status' => UserStatus::PENDING->value,
        ]);

        $supervisor->assignRole(UserRole::Supervisor->value);

        $response = $this->putJson(
            "/api/admin/users/{$supervisor->id}/approve"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $supervisor->id,
            'status' => UserStatus::ACTIVE->value,
        ]);
    }
    public function test_admin_can_reject_pending_supervisor(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        Sanctum::actingAs($admin);

        $supervisor = User::factory()->create([
            'status' => UserStatus::PENDING->value,
        ]);

        $supervisor->assignRole(UserRole::Supervisor->value);

        $response = $this->putJson(
            "/api/admin/users/{$supervisor->id}/reject"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $supervisor->id,
            'status' => UserStatus::REJECTED->value,
        ]);
    }
    public function test_admin_cannot_approve_student(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        Sanctum::actingAs($admin);

        $student = User::factory()->create([
            'status' => UserStatus::PENDING->value,
        ]);

        $student->assignRole(UserRole::Student->value);

        $response = $this->putJson(
            "/api/admin/users/{$student->id}/approve"
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'status' => UserStatus::PENDING->value,
        ]);
    }
    public function test_admin_can_approve_pending_committee_member(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        Sanctum::actingAs($admin);

        $committeeMember = User::factory()->create([
            'status' => UserStatus::PENDING->value,
        ]);

        $committeeMember->assignRole(UserRole::CommitteeMember->value);

        $response = $this->putJson(
            "/api/admin/users/{$committeeMember->id}/approve"
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $committeeMember->id,
            'status' => UserStatus::ACTIVE->value,
        ]);
    }
    public function test_admin_cannot_approve_already_active_supervisor(): void
    {
        $admin = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $admin->assignRole(UserRole::Admin->value);

        Sanctum::actingAs($admin);

        $supervisor = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $supervisor->assignRole(UserRole::Supervisor->value);

        $response = $this->putJson(
            "/api/admin/users/{$supervisor->id}/approve"
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $supervisor->id,
            'status' => UserStatus::ACTIVE->value,
        ]);
    }
    public function test_non_admin_cannot_approve_supervisor(): void
    {
        $student = User::factory()->create([
            'status' => UserStatus::ACTIVE->value,
        ]);

        $student->assignRole(UserRole::Student->value);

        Sanctum::actingAs($student);

        $supervisor = User::factory()->create([
            'status' => UserStatus::PENDING->value,
        ]);

        $supervisor->assignRole(UserRole::Supervisor->value);

        $response = $this->putJson(
            "/api/admin/users/{$supervisor->id}/approve"
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $supervisor->id,
            'status' => UserStatus::PENDING->value,
        ]);
    }
}
