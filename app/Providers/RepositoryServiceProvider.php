<?php

namespace App\Providers;

use App\Interfaces\ProfileRepositoryInterface;
use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectJoinRequestRepositoryInterface;
use App\Interfaces\ProjectProposalCommitteeReviewRepositoryInterface;
use App\Interfaces\ProjectProposalRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Interfaces\TaskAttachmentRepositoryInterface;
use App\Interfaces\TaskLinkRepositoryInterface;
use App\Interfaces\TaskReviewRepositoryInterface;
use App\Interfaces\TaskRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\ProfileRepository;
use App\Repositories\ProjectIdeaMatchRepository;
use App\Repositories\ProjectIdeaRepository;
use App\Repositories\ProjectJoinRequestRepository;
use App\Repositories\ProjectProposalCommitteeReviewRepository;
use App\Repositories\ProjectProposalRepository;
use App\Repositories\ProjectTeamMemberRepository;
use App\Repositories\ProjectTeamRepository;
use App\Repositories\TaskAttachmentRepository;
use App\Repositories\TaskLinkRepository;
use App\Repositories\TaskReviewRepository;
use App\Repositories\TaskRepository;
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
        $this->app->bind(ProjectProposalRepositoryInterface::class, ProjectProposalRepository::class);
        $this->app->bind(ProjectProposalCommitteeReviewRepositoryInterface::class, ProjectProposalCommitteeReviewRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TaskAttachmentRepositoryInterface::class, TaskAttachmentRepository::class);
        $this->app->bind(TaskLinkRepositoryInterface::class, TaskLinkRepository::class);
        $this->app->bind(TaskReviewRepositoryInterface::class, TaskReviewRepository::class);
    }
}
