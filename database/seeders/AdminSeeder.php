<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Kareem',
                'username' => 'kareem',
                'email' => 'kareem@devpulse.local',
            ],
            [
                'name' => 'Mohamed',
                'username' => 'mohamed',
                'email' => 'mohamed@devpulse.local',
            ],
        ];

        foreach ($admins as $admin) {
            $user = User::query()->updateOrCreate(
                ['username' => $admin['username']],
                [
                    ...$admin,
                    'password' => Hash::make('8888888'),
                    'status' => UserStatus::ACTIVE->value,
                    'profile_completed' => true,
                ]
            );

            $user->assignRole(UserRole::Admin->value);
        }
    }
}
