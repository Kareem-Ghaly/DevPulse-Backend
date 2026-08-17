<?php

namespace Tests\Unit\Matching;

use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Services\ProjectIdeaMatchingService;
use Mockery;
use Tests\TestCase;

class SupervisorMatchingTest extends TestCase
{
    public function test_supervisor_matching_returns_404_when_project_idea_does_not_exist(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $projectIdeas
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->supervisors(999);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
