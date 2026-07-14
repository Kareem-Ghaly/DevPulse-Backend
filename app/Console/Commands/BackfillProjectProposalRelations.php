<?php

namespace App\Console\Commands;

use App\Models\ProjectProposal;
use App\Models\ProjectTeam;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillProjectProposalRelations extends Command
{
    protected $signature = 'project-proposals:backfill-relations {--dry-run : Show what would be changed without updating records}';

    protected $description = 'Safely backfill project proposal project_team_id and supervisor_id relationships.';

    public function handle(): int
    {
        $linked = [];
        $skipped = [];
        $dryRun = (bool) $this->option('dry-run');

        ProjectProposal::query()
            ->with(['team', 'supervisorUser'])
            ->orderBy('id')
            ->each(function (ProjectProposal $proposal) use (&$linked, &$skipped, $dryRun): void {
                $updates = [];
                $changes = [];

                if ($proposal->project_team_id === null) {
                    [$teamId, $reason] = $this->resolveTeamId($proposal);

                    if ($teamId !== null) {
                        $updates['project_team_id'] = $teamId;
                        $changes[] = "project_team_id={$teamId}";
                    } else {
                        $skipped[] = [
                            'id' => $proposal->id,
                            'field' => 'project_team_id',
                            'reason' => $reason,
                        ];
                    }
                }

                if ($proposal->supervisor_id === null) {
                    [$supervisor, $reason] = $this->resolveSupervisor($proposal);

                    if ($supervisor) {
                        $updates['supervisor_id'] = $supervisor->id;
                        $updates['supervisor'] = $supervisor->name;
                        $changes[] = "supervisor_id={$supervisor->id}";
                    } elseif ($proposal->supervisor !== null && trim((string) $proposal->supervisor) !== '') {
                        $skipped[] = [
                            'id' => $proposal->id,
                            'field' => 'supervisor_id',
                            'reason' => $reason,
                        ];
                    }
                }

                if ($updates !== []) {
                    if (! $dryRun) {
                        $proposal->forceFill($updates)->save();
                    }

                    $linked[] = [
                        'id' => $proposal->id,
                        'changes' => implode(', ', $changes),
                    ];
                }
            });

        $this->info($dryRun ? 'Dry run complete.' : 'Backfill complete.');

        $this->line('Linked proposals:');
        if ($linked === []) {
            $this->line('- none');
        } else {
            foreach ($linked as $item) {
                $this->line("- proposal {$item['id']}: {$item['changes']}");
            }
        }

        $this->line('Skipped proposals:');
        if ($skipped === []) {
            $this->line('- none');
        } else {
            foreach ($skipped as $item) {
                $this->line("- proposal {$item['id']} {$item['field']}: {$item['reason']}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveTeamId(ProjectProposal $proposal): array
    {
        foreach (['created_by', 'last_updated_by'] as $field) {
            $userId = $proposal->{$field};

            if (! $userId) {
                continue;
            }

            $teamIds = $this->teamIdsForUser((int) $userId);

            if ($teamIds->count() === 1) {
                return [(int) $teamIds->first(), null];
            }

            if ($teamIds->count() > 1) {
                return [null, "{$field} user belongs to multiple project teams"];
            }
        }

        return [null, 'no created_by or last_updated_by user with exactly one project team'];
    }

    private function teamIdsForUser(int $userId): Collection
    {
        return ProjectTeam::query()
            ->where(function ($query) use ($userId): void {
                $query->where('leader_id', $userId)
                    ->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $userId));
            })
            ->pluck('id')
            ->unique()
            ->values();
    }

    private function resolveSupervisor(ProjectProposal $proposal): array
    {
        $legacySupervisor = trim((string) $proposal->supervisor);

        if ($legacySupervisor === '') {
            return [null, 'legacy supervisor is empty'];
        }

        if (is_numeric($legacySupervisor)) {
            $user = User::query()->with('roles')->find((int) $legacySupervisor);

            return $user && $user->hasRole('Supervisor')
                ? [$user, null]
                : [null, 'numeric supervisor value does not match a Supervisor user'];
        }

        if (filter_var($legacySupervisor, FILTER_VALIDATE_EMAIL)) {
            $matches = User::query()
                ->with('roles')
                ->role('Supervisor')
                ->where('email', $legacySupervisor)
                ->get();

            return $matches->count() === 1
                ? [$matches->first(), null]
                : [null, 'email supervisor value does not match exactly one Supervisor user'];
        }

        $matches = User::query()
            ->with('roles')
            ->role('Supervisor')
            ->where('name', $legacySupervisor)
            ->get();

        return $matches->count() === 1
            ? [$matches->first(), null]
            : [null, 'name supervisor value does not match exactly one Supervisor user'];
    }
}