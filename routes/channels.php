<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ProjectTeam;

Broadcast::channel('proposal.team.{teamId}', function ($user, $teamId) {
    $team = ProjectTeam::find($teamId);
    
    if (!$team) {
        return false;
    }
    
    return $team->leader_id === $user->id 
        || $team->members()->where('user_id', $user->id)->exists();
});

Broadcast::channel('project-team.{teamId}.tasks', function ($user, $teamId) {
    $team = ProjectTeam::find($teamId);
    
    if (!$team) {
        return false;
    }
    
    return $team->leader_id === $user->id 
        || $team->members()->where('user_id', $user->id)->exists();
});