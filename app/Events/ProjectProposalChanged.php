<?php

namespace App\Events;

use App\Models\ProjectProposal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectProposalChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ProjectProposal $proposal,
        public string $action
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('proposal.team.' . $this->proposal->project_team_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'proposal.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'proposal' => new \App\Http\Resources\ProjectProposalResource($this->proposal),
        ];
    }
}