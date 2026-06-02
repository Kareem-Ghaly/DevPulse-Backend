<?php

namespace App\Providers;

use App\Interfaces\ProfileRepositoryInterface;
use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectJoinRequestRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\ProfileRepository;
use App\Repositories\ProjectIdeaMatchRepository;
use App\Repositories\ProjectIdeaRepository;
use App\Repositories\ProjectJoinRequestRepository;
use App\Repositories\ProjectTeamMemberRepository;
use App\Repositories\ProjectTeamRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(ProjectIdeaRepositoryInterface::class, ProjectIdeaRepository::class);
        $this->app->bind(ProjectIdeaMatchRepositoryInterface::class, ProjectIdeaMatchRepository::class);
        $this->app->bind(ProjectJoinRequestRepositoryInterface::class, ProjectJoinRequestRepository::class);
        $this->app->bind(ProjectTeamRepositoryInterface::class, ProjectTeamRepository::class);
        $this->app->bind(ProjectTeamMemberRepositoryInterface::class, ProjectTeamMemberRepository::class);
    }
}
