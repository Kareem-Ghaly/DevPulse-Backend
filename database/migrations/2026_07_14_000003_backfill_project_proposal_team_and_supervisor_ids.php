<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_proposals')) {
            return;
        }

        $this->backfillProjectTeams();
        $this->backfillSupervisors();
    }

    public function down(): void
    {
        // Data backfills are intentionally not reversed.
    }

    private function backfillProjectTeams(): void
    {
        if (! Schema::hasColumn('project_proposals', 'project_team_id') || ! Schema::hasColumn('project_proposals', 'created_by')) {
            return;
        }

        DB::table('project_proposals')
            ->whereNull('project_team_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $proposal): void {
                if (! $proposal->created_by) {
                    $this->logUnmatchedTeam($proposal->id, 'proposal has no created_by value');

                    return;
                }

                $teamIds = DB::table('project_teams')
                    ->where('leader_id', $proposal->created_by)
                    ->pluck('id')
                    ->merge(
                        DB::table('project_team_members')
                            ->where('user_id', $proposal->created_by)
                            ->pluck('project_team_id')
                    )
                    ->unique()
                    ->values();

                if ($teamIds->count() === 1) {
                    DB::table('project_proposals')
                        ->where('id', $proposal->id)
                        ->update(['project_team_id' => $teamIds->first()]);

                    return;
                }

                $reason = $teamIds->isEmpty()
                    ? 'creator is not linked to any project team'
                    : 'creator is linked to multiple project teams';

                $this->logUnmatchedTeam($proposal->id, $reason);
            });
    }

    private function backfillSupervisors(): void
    {
        if (! Schema::hasColumn('project_proposals', 'supervisor') || ! Schema::hasColumn('project_proposals', 'supervisor_id')) {
            return;
        }

        DB::table('project_proposals')
            ->whereNull('supervisor_id')
            ->whereNotNull('supervisor')
            ->orderBy('id')
            ->get()
            ->each(function (object $proposal): void {
                $legacySupervisor = trim((string) $proposal->supervisor);
                $supervisor = $this->matchSupervisor($legacySupervisor);

                if ($supervisor) {
                    DB::table('project_proposals')
                        ->where('id', $proposal->id)
                        ->update([
                            'supervisor_id' => $supervisor->id,
                            'supervisor' => $supervisor->name,
                        ]);

                    return;
                }

                Log::warning('Project proposal supervisor backfill skipped', [
                    'proposal_id' => $proposal->id,
                    'legacy_supervisor' => $legacySupervisor,
                    'reason' => 'no unique Supervisor user matched numeric id, email, or exact name',
                ]);
            });
    }

    private function matchSupervisor(string $value): ?User
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $user = User::query()->find((int) $value);

            return $user && $user->hasRole('Supervisor') ? $user : null;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()->where('email', $value)->first();

            return $user && $user->hasRole('Supervisor') ? $user : null;
        }

        $matches = User::query()
            ->role('Supervisor')
            ->where('name', $value)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function logUnmatchedTeam(int $proposalId, string $reason): void
    {
        Log::warning('Project proposal team backfill skipped', [
            'proposal_id' => $proposalId,
            'reason' => $reason,
        ]);
    }
};