<?php

namespace App\Events;

use App\Models\ProjectProposal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('project-proposals'),
            new Channel('project-proposal.'.$this->proposal->id),
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
            'proposal_id' => $this->proposal->id,
            'title' => $this->proposal->title,
            'status' => $this->proposal->status,
        ];
    }
}