<?php

namespace App\Services;

use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\RiskAttachment;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Departments;
use Illuminate\Support\Carbon;

/**
 * Phase 5 slice 24 + Phase 7 slice 6 + slice 13 + Phase 8 slice 3–5: Department Head ticket detail (read + workflow + comment + documents + personnel + comment edit/react).
 */
class DeptTicketDetailService
{
    /** @var list<string> */
    private const VISIBLE_STATUSES = [
        'pending_ai_review',
        'assigned',
        'in_progress',
        'ownership_rejected',
        'under_audit',
        'audit_returned',
        'pending_president',
        'in_mitigation',
        'pending_audit',
        'pending_president_final',
        'resolved',
        'closed',
        'reopened',
    ];

    /**
     * @return array{
     *   ticket: array<string, mixed>,
     *   fiveW1H: array<string, string>,
     *   attachments: list<array<string, mixed>>,
     *   planDocuments: list<array<string, mixed>>,
     *   actionPlan: array<string, mixed>|null,
     *   accomplishment: array<string, mixed>|null,
     *   timeline: list<array<string, mixed>>,
     *   reassignments: list<array<string, mixed>>,
     *   personnel: list<array<string, mixed>>,
     *   threadComments: list<array<string, mixed>>,
     *   departments: list<string>,
     *   capabilities: array<string, bool>,
     *   stats: array<string, int>,
     *   activeNav: string
     * }|null
     */
    public function forUser(User $user, string $reference): ?array
    {
        $ticket = RiskTicket::query()
            ->where('reference', $reference)
            ->where('deleted', false)
            ->where('status', '!=', 'draft')
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->first();

        if (! $ticket || ! $this->isDeptHeadTicket($ticket, $user)) {
            return null;
        }

        $status = (string) $ticket->status;
        $ownership = is_array($ticket->ownership) ? $ticket->ownership : [];
        $ai = is_array($ticket->ai) ? $ticket->ai : [];
        $plan = is_array($ticket->action_plan) ? $ticket->action_plan : null;
        $five = is_array($ticket->five_w1h) ? $ticket->five_w1h : [];
        $isOwner = ($ownership['ownerUsername'] ?? null) === $user->username;
        $canExecute = in_array($status, ['in_progress', 'reopened'], true) && $isOwner;
        $returnedByPresident = $this->returnedByPresident($ticket);
        $riskLevel = Departments::riskLevelId($ai, $ticket->likelihood, $ticket->impact);
        $due = $this->dueAt($ticket);

        $accomplishment = Accomplishment::query()
            ->where('ticket_ref', $ticket->reference)
            ->orderByDesc('submitted_at')
            ->first();

        $canClose = $status === 'pending_audit'
            && $accomplishment !== null
            && ! Departments::requiresPresidentApproval(
                is_array($ticket->ai) ? $ticket->ai : null,
                $ticket->likelihood,
                $ticket->impact,
            );
        $planPublished = $plan && (! empty($plan['publishedToReporterAt']) || ! empty($plan['submittedForReviewAt']));
        $isDraftPlan = $plan && ! empty(trim((string) ($plan['summary'] ?? ''))) && ! $planPublished;

        $payload = is_array($ticket->payload) ? $ticket->payload : [];
        $planDocIds = array_values(array_filter(
            array_merge(
                is_array($payload['actionPlanDocumentIds'] ?? null) ? $payload['actionPlanDocumentIds'] : [],
                is_array($payload['implementationEvidenceIds'] ?? null) ? $payload['implementationEvidenceIds'] : [],
            ),
            fn ($id) => is_string($id) && $id !== '',
        ));
        $planDocIdSet = array_fill_keys($planDocIds, true);

        $allAttachments = RiskAttachment::query()
            ->where('ticket_ref', $ticket->reference)
            ->orderByDesc('uploaded_at')
            ->get()
            ->map(fn (RiskAttachment $a) => [
                'id' => $a->id,
                'name' => $a->original_name ?: 'file',
                'size' => (int) $a->size_bytes,
                'uploadedAt' => optional($a->uploaded_at)?->toIso8601String(),
                'uploadedBy' => $a->uploaded_by,
            ]);

        $planDocuments = $allAttachments
            ->filter(fn (array $file) => isset($planDocIdSet[$file['id']]))
            ->values()
            ->all();

        $attachments = $allAttachments
            ->reject(fn (array $file) => isset($planDocIdSet[$file['id']]))
            ->values()
            ->all();

        $timeline = [];
        foreach (array_slice(array_reverse(is_array($ticket->audit_trail) ? $ticket->audit_trail : []), 0, 25) as $event) {
            if (! is_array($event)) {
                continue;
            }
            $timeline[] = [
                'action' => (string) ($event['action'] ?? 'Event'),
                'detail' => (string) ($event['detail'] ?? ''),
                'actorName' => (string) ($event['actorName'] ?? $event['actorUsername'] ?? ''),
                'at' => (string) ($event['at'] ?? ''),
            ];
        }

        $reassignments = [];
        foreach (array_reverse(is_array($ticket->reassignments) ? $ticket->reassignments : []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $reassignments[] = [
                'fromDepartment' => (string) ($row['fromDepartment'] ?? ''),
                'toDepartment' => (string) ($row['toDepartment'] ?? ''),
                'byName' => (string) ($row['byName'] ?? $row['byUsername'] ?? ''),
                'reason' => (string) ($row['reason'] ?? ''),
                'at' => (string) ($row['at'] ?? ''),
            ];
        }

        $departments = Department::query()
            ->where('active', true)
            ->where('status', '!=', 'inactive')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '' && ! Departments::match($name, $ticket->department))
            ->values()
            ->all();

        $dashboard = app(DeptDashboardService::class)->forUser($user);
        $ownerState = (string) ($ownership['state'] ?? ($status === 'assigned' ? 'pending' : 'unassigned'));

        $activeNav = 'tickets';
        if ($status === 'assigned') {
            $activeNav = 'inbox';
        } elseif ($returnedByPresident) {
            $activeNav = 'returned';
        } elseif ($isDraftPlan && $isOwner) {
            $activeNav = 'drafts';
        } elseif ($status === 'pending_audit') {
            $activeNav = 'closure';
        } elseif (in_array($status, ['in_progress', 'reopened'], true)) {
            $activeNav = 'active';
        }

        $presidentPlan = is_array($ticket->president_plan_decision) ? $ticket->president_plan_decision : null;
        $presidentFinal = is_array($ticket->president_final_decision) ? $ticket->president_final_decision : null;
        $closure = is_array($ticket->closure) ? $ticket->closure : null;

        return [
            'ticket' => [
                'reference' => $ticket->reference,
                'title' => $ticket->title ?: '—',
                'description' => $ticket->description ?: '—',
                'location' => $ticket->location ?: '—',
                'status' => $status,
                'statusLabel' => $this->statusLabel($status),
                'category' => $ticket->category ?: '—',
                'categoryLabel' => $ticket->category
                    ? str_replace('_', ' ', ucfirst((string) $ticket->category))
                    : '—',
                'priority' => $ticket->priority ?: null,
                'department' => $ticket->department ?: '—',
                'reporterDepartment' => $ticket->reporter_department ?: '—',
                'submittedBy' => $ticket->submitted_by,
                'submittedByName' => $ticket->submitted_by_name ?: $ticket->submitted_by ?: '—',
                'likelihood' => $ticket->likelihood,
                'impact' => $ticket->impact,
                'riskScore' => $ticket->risk_score
                    ?: (($ticket->likelihood && $ticket->impact)
                        ? ((int) $ticket->likelihood * (int) $ticket->impact)
                        : null),
                'riskLevel' => $riskLevel,
                'riskLevelLabel' => $this->riskLevelLabel($riskLevel),
                'dueAt' => $due?->toIso8601String(),
                'isOverdue' => $this->isOverdue($ticket),
                'updatedAt' => optional($ticket->source_updated_at)?->toIso8601String(),
                'submittedAt' => optional($ticket->submitted_at)?->toIso8601String(),
                'ownershipState' => $ownerState,
                'ownershipLabel' => match ($ownerState) {
                    'pending' => 'Awaiting acceptance',
                    'accepted' => 'Owned',
                    'rejected' => 'Rejected',
                    default => 'Unassigned',
                },
                'ownershipTone' => match ($ownerState) {
                    'pending' => 'info',
                    'accepted' => 'rmo',
                    'rejected' => 'warn',
                    default => 'muted',
                },
                'ownerName' => $ownership['ownerName'] ?? null,
                'rejectionReason' => $ownership['rejectionReason'] ?? null,
                'reassignedFrom' => $ownership['reassignedFrom'] ?? null,
                'aiSummary' => $ai['summary'] ?? ($ai['rationale'] ?? null),
                'aiConfidence' => $ai['confidence'] ?? null,
                'aiLikelihood' => $ai['likelihood'] ?? null,
                'aiImpact' => $ai['impact'] ?? null,
                'suggestedMitigation' => $ai['suggestedMitigation'] ?? null,
                'returnedByPresident' => $returnedByPresident,
                'presidentPlanNote' => is_array($presidentPlan) ? trim((string) ($presidentPlan['note'] ?? '')) : '',
                'presidentFinalNote' => is_array($presidentFinal) ? trim((string) ($presidentFinal['note'] ?? '')) : '',
                'closureByName' => $closure['closedByName'] ?? null,
                'closureAt' => $closure['closedAt'] ?? null,
                'needsPresident' => in_array($riskLevel, ['high', 'critical'], true),
            ],
            'fiveW1H' => [
                'what' => (string) ($five['what'] ?? ''),
                'why' => (string) ($five['why'] ?? ''),
                'where' => (string) ($five['where'] ?? ''),
                'when' => (string) ($five['when'] ?? ''),
                'who' => (string) ($five['who'] ?? ''),
                'how' => (string) ($five['how'] ?? ''),
            ],
            'attachments' => $attachments,
            'planDocuments' => $planDocuments,
            'actionPlan' => $plan && trim((string) ($plan['summary'] ?? '')) !== '' ? [
                'summary' => (string) ($plan['summary'] ?? ''),
                'steps' => array_values(array_filter(array_map(
                    fn ($s) => trim((string) $s),
                    is_array($plan['steps'] ?? null) ? $plan['steps'] : [],
                ))),
                'targetDate' => isset($plan['targetDate']) ? substr((string) $plan['targetDate'], 0, 10) : '',
                'version' => (int) ($plan['version'] ?? 1),
                'updatedAt' => $plan['updatedAt'] ?? null,
                'updatedByName' => $plan['updatedByName'] ?? null,
                'isDraft' => (bool) $isDraftPlan,
                'publishedAt' => $plan['publishedToReporterAt'] ?? ($plan['submittedForReviewAt'] ?? null),
            ] : null,
            'accomplishment' => $accomplishment ? [
                'summary' => $accomplishment->summary ?: '—',
                'outcomes' => $accomplishment->outcomes ?: '—',
                'submittedAt' => optional($accomplishment->submitted_at)?->toIso8601String(),
            ] : null,
            'timeline' => $timeline,
            'reassignments' => $reassignments,
            'personnel' => $this->personnelList($ticket),
            'threadComments' => $this->normalizeThreadComments(
                is_array($ticket->thread_comments) ? $ticket->thread_comments : []
            ),
            'departments' => $departments,
            'capabilities' => [
                'canAccept' => $status === 'assigned',
                'canReject' => $status === 'assigned',
                'canReassign' => $status === 'assigned' || $canExecute,
                'canReturn' => $canExecute,
                'canEditActionPlan' => $canExecute,
                'canClose' => $canClose,
                'canPostComment' => true,
                'canUploadDocuments' => $isOwner && in_array($status, ['in_progress', 'reopened', 'in_mitigation'], true),
                'canAssignPersonnel' => $canExecute,
            ],
            'stats' => $dashboard['stats'],
            'activeNav' => $activeNav,
        ];
    }

    private function isDeptHeadTicket(RiskTicket $ticket, User $user): bool
    {
        $ownership = is_array($ticket->ownership) ? $ticket->ownership : [];
        if (($ownership['ownerUsername'] ?? null) === $user->username) {
            return true;
        }
        if (Departments::match($user->department, $ticket->department)) {
            return true;
        }
        if (Departments::match($user->department, $ownership['ownerDepartment'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function personnelList(RiskTicket $ticket): array
    {
        $rows = [];
        foreach (is_array($ticket->personnel) ? $ticket->personnel : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $rows[] = [
                'id' => (string) ($row['id'] ?? ''),
                'name' => $name,
                'role' => trim((string) ($row['role'] ?? '')) ?: null,
                'assignedAt' => (string) ($row['assignedAt'] ?? ''),
                'assignedByName' => (string) ($row['assignedByName'] ?? ''),
            ];
        }

        return $rows;
    }

    private function returnedByPresident(RiskTicket $ticket): bool
    {
        $plan = is_array($ticket->president_plan_decision) ? $ticket->president_plan_decision : null;
        if (
            $plan
            && in_array((string) ($plan['decisionId'] ?? ''), ['return', 'reject'], true)
            && (string) $ticket->status === 'in_progress'
        ) {
            return true;
        }

        $final = is_array($ticket->president_final_decision) ? $ticket->president_final_decision : null;

        return $final
            && ($final['decisionId'] ?? null) === 'return'
            && in_array((string) $ticket->status, ['in_mitigation', 'in_progress', 'reopened'], true);
    }

    private function dueAt(RiskTicket $ticket): ?Carbon
    {
        $plan = is_array($ticket->action_plan) ? $ticket->action_plan : [];
        $raw = $plan['targetDate'] ?? null;
        if (is_string($raw) && $raw !== '') {
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                // fall through
            }
        }

        return $ticket->mitigation_due_at;
    }

    private function isOverdue(RiskTicket $ticket): bool
    {
        $status = (string) $ticket->status;
        if (in_array($status, ['closed', 'resolved', 'draft', 'returned', 'ownership_rejected', 'pending_audit', 'pending_president_final'], true)) {
            return false;
        }
        if ($ticket->accomplishment_external_id) {
            return false;
        }
        $due = $this->dueAt($ticket);

        return $due !== null && now()->greaterThan($due);
    }

    private function riskLevelLabel(?string $riskLevel): string
    {
        return match ($riskLevel) {
            'low' => 'Low',
            'moderate' => 'Moderate',
            'high' => 'High',
            'critical' => 'Critical',
            default => $riskLevel ? ucfirst(str_replace('_', ' ', $riskLevel)) : '—',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'assigned' => 'Assigned',
            'in_progress' => 'In progress',
            'ownership_rejected' => 'Ownership rejected',
            'pending_president' => 'Awaiting President',
            'pending_audit' => 'Pending closure',
            'pending_president_final' => 'Awaiting President (final)',
            'in_mitigation' => 'In mitigation',
            'reopened' => 'Reopened',
            'closed' => 'Closed',
            'resolved' => 'Resolved',
            default => $status ? str_replace('_', ' ', ucfirst($status)) : '—',
        };
    }

    /**
     * @param  list<mixed>  $comments
     * @return list<array<string, mixed>>
     */
    private function normalizeThreadComments(array $comments): array
    {
        $rows = [];
        foreach ($comments as $c) {
            if (! is_array($c) || empty($c['id'])) {
                continue;
            }
            $rows[] = [
                'id' => (string) $c['id'],
                'body' => (string) ($c['body'] ?? ''),
                'authorName' => (string) ($c['authorName'] ?? $c['authorUsername'] ?? 'Unknown'),
                'authorUsername' => (string) ($c['authorUsername'] ?? ''),
                'roleLabel' => (string) ($c['authorPosition'] ?? $c['roleLabel'] ?? $c['authorRole'] ?? ''),
                'kind' => (string) ($c['kind'] ?? 'comment'),
                'parentId' => isset($c['parentId']) && $c['parentId'] ? (string) $c['parentId'] : null,
                'at' => (string) ($c['at'] ?? ''),
                'editedAt' => (string) ($c['editedAt'] ?? ''),
                'reactions' => $this->normalizeReactions($c['reactions'] ?? null),
            ];
        }

        usort($rows, fn (array $a, array $b) => strcmp($a['at'] ?? '', $b['at'] ?? ''));

        return $rows;
    }

    /**
     * @return array<string, list<string>>
     */
    private function normalizeReactions(mixed $raw): array
    {
        $reactions = [];
        foreach (is_array($raw) ? $raw : [] as $emoji => $users) {
            $names = array_values(array_filter(
                is_array($users) ? $users : [],
                fn ($name) => is_string($name) && $name !== '',
            ));
            if ($names !== []) {
                $reactions[(string) $emoji] = $names;
            }
        }

        return $reactions;
    }
}
