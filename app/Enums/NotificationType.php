<?php

namespace App\Enums;

enum NotificationType: string
{
    case AccountPendingApproval = 'account_pending_approval';
    case AccountApproved = 'account_approved';
    case AccountRejected = 'account_rejected';

    case AnnouncementPublished = 'announcement_published';

    case TeamInvitationSent = 'team_invitation_sent';
    case TeamInvitationAccepted = 'team_invitation_accepted';
    case TeamInvitationRejected = 'team_invitation_rejected';
    case TeamCompleted = 'team_completed';

    case SupervisorRequestSubmitted = 'supervisor_request_submitted';
    case SupervisorProposalApproved = 'supervisor_proposal_approved';
    case SupervisorProposalRejected = 'supervisor_proposal_rejected';
    case SupervisorProposalNeedsRevision = 'supervisor_proposal_needs_revision';

    case ProposalSubmittedToCommittee = 'proposal_submitted_to_committee';
    case CommitteeProposalApproved = 'committee_proposal_approved';
    case CommitteeProposalRejected = 'committee_proposal_rejected';
    case CommitteeProposalNeedsRevision = 'committee_proposal_needs_revision';

    case TaskAssigned = 'task_assigned';
    case TaskUpdated = 'task_updated';
    case TaskStatusChanged = 'task_status_changed';
    case TaskReviewAdded = 'task_review_added';

    case MeetingScheduled = 'meeting_scheduled';

    case FinalSubmissionReceived = 'final_submission_received';
    case FinalSubmissionGraded = 'final_submission_graded';
}
