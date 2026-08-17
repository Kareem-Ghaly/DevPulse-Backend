<?php

namespace Tests\Unit\Matching;

use App\Interfaces\ProjectIdeaMatchRepositoryInterface;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectTeamMemberRepositoryInterface;
use App\Interfaces\ProjectTeamRepositoryInterface;
use App\Services\ProjectIdeaMatchingService;
use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProjectIdea;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class StudentMatchingTest extends TestCase
{
    public function test_student_matching_returns_422_when_required_skills_are_empty(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $user = new User();
        $user->id = 1;

        $this->actingAs($user);


        $projectIdea = new ProjectIdea();

        $projectIdea->id = 1;
        $projectIdea->owner_id = 1;
        $projectIdea->required_skills = [];

        $projectIdeas
            ->shouldReceive('findOwnedByUser')
            ->once()
            ->with(1, 1)
            ->andReturn($projectIdea);

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->students(1);

        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_student_cannot_view_matching_for_project_idea_he_does_not_own(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $user = new User();
        $user->id = 1;

        $this->actingAs($user);

        $projectIdeas
            ->shouldReceive('findOwnedByUser')
            ->once()
            ->with(5, 1)
            ->andReturn(null);

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->students(5);

        $this->assertEquals(403, $response->getStatusCode());
    }
    public function test_student_matching_calculates_correct_match_percentage(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $owner = new User();
        $owner->id = 1;

        $this->actingAs($owner);

        $projectIdea = new ProjectIdea();
        $projectIdea->id = 10;
        $projectIdea->owner_id = 1;
        $projectIdea->required_skills = [
            'Laravel',
            'PHP',
            'MySQL',
        ];

        $student = new User();
        $student->id = 2;
        $student->name = 'Test Student';
        $student->email = 'student@test.com';

        $profile = new StudentProfile();
        $profile->skills = [
            'Laravel',
            'PHP',
        ];

        $profile->setRelation('user', $student);

        $projectIdeas
            ->shouldReceive('findOwnedByUser')
            ->once()
            ->with(10, 1)
            ->andReturn($projectIdea);

        $teams
            ->shouldReceive('findByProjectIdea')
            ->once()
            ->with(10)
            ->andReturn(null);

        $matchRepository
            ->shouldReceive('getMatchableStudentProfiles')
            ->once()
            ->with(1, [])
            ->andReturn(collect([$profile]));

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->students(10);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertEquals(
            67,
            $data['data']['matches'][0]['match_percentage']
        );

        $this->assertEquals(
            ['Laravel', 'PHP'],
            $data['data']['matches'][0]['matched_skills']
        );

        $this->assertEquals(
            ['MySQL'],
            $data['data']['matches'][0]['missing_skills']
        );
    }
    public function test_student_matching_returns_100_percent_when_all_skills_match(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $owner = new User();
        $owner->id = 1;

        $this->actingAs($owner);

        $projectIdea = new ProjectIdea();
        $projectIdea->id = 10;
        $projectIdea->owner_id = 1;
        $projectIdea->required_skills = [
            'Laravel',
            'PHP',
            'MySQL',
        ];

        $student = new User();
        $student->id = 2;
        $student->name = 'Perfect Match Student';
        $student->email = 'perfect@test.com';

        $profile = new StudentProfile();
        $profile->skills = [
            'Laravel',
            'PHP',
            'MySQL',
        ];

        $profile->setRelation('user', $student);

        $projectIdeas
            ->shouldReceive('findOwnedByUser')
            ->once()
            ->with(10, 1)
            ->andReturn($projectIdea);

        $teams
            ->shouldReceive('findByProjectIdea')
            ->once()
            ->with(10)
            ->andReturn(null);

        $matchRepository
            ->shouldReceive('getMatchableStudentProfiles')
            ->once()
            ->with(1, [])
            ->andReturn(collect([$profile]));

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->students(10);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertEquals(
            100,
            $data['data']['matches'][0]['match_percentage']
        );

        $this->assertEquals(
            ['Laravel', 'PHP', 'MySQL'],
            $data['data']['matches'][0]['matched_skills']
        );

        $this->assertEmpty(
            $data['data']['matches'][0]['missing_skills']
        );
    }
    public function test_student_matching_returns_zero_percent_when_no_skills_match(): void
    {
        $projectIdeas = Mockery::mock(ProjectIdeaRepositoryInterface::class);
        $matchRepository = Mockery::mock(ProjectIdeaMatchRepositoryInterface::class);
        $teams = Mockery::mock(ProjectTeamRepositoryInterface::class);
        $teamMembers = Mockery::mock(ProjectTeamMemberRepositoryInterface::class);

        $owner = new User();
        $owner->id = 1;

        $this->actingAs($owner);

        $projectIdea = new ProjectIdea();
        $projectIdea->id = 10;
        $projectIdea->owner_id = 1;
        $projectIdea->required_skills = [
            'Laravel',
            'PHP',
            'MySQL',
        ];

        $student = new User();
        $student->id = 2;
        $student->name = 'No Match Student';
        $student->email = 'nomatch@test.com';

        $profile = new StudentProfile();
        $profile->skills = [
            'Python',
            'Django',
            'PostgreSQL',
        ];

        $profile->setRelation('user', $student);

        $projectIdeas
            ->shouldReceive('findOwnedByUser')
            ->once()
            ->with(10, 1)
            ->andReturn($projectIdea);

        $teams
            ->shouldReceive('findByProjectIdea')
            ->once()
            ->with(10)
            ->andReturn(null);

        $matchRepository
            ->shouldReceive('getMatchableStudentProfiles')
            ->once()
            ->with(1, [])
            ->andReturn(collect([$profile]));

        $service = new ProjectIdeaMatchingService(
            $projectIdeas,
            $matchRepository,
            $teams,
            $teamMembers
        );

        $response = $service->students(10);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertEquals(
            0,
            $data['data']['matches'][0]['match_percentage']
        );

        $this->assertEmpty(
            $data['data']['matches'][0]['matched_skills']
        );

        $this->assertEquals(
            ['Laravel', 'PHP', 'MySQL'],
            $data['data']['matches'][0]['missing_skills']
        );
    }
}
