<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use App\Services\DeptTicketService;
use App\Services\DraftTicketService;
use App\Services\SubmitTicketService;
use App\Support\Roles;
use Illuminate\Console\Command;

/**
 * Smoke-test dept return, reassign, and close (Postgres only).
 */
class SmokeDeptWorkflow extends Command
{
    protected $signature = 'rms:smoke-dept-s6
                            {--reporter=admin}
                            {--dept-head= : IT dept head username}
                            {--target-dept=Finance : Reassign target department name}';

    protected $description = 'Smoke return→reassign→close dept workflow in Laravel (does not touch Express)';

    public function handle(
        DraftTicketService $drafts,
        SubmitTicketService $submitter,
        DeptTicketService $deptTickets,
    ): int {
        Department::query()->firstOrCreate(
            ['external_id' => 'dept-it-smoke'],
            ['name' => 'Information Technology', 'code' => 'IT', 'active' => true, 'status' => 'active'],
        );
        Department::query()->firstOrCreate(
            ['external_id' => 'dept-fin-smoke'],
            ['name' => 'Finance', 'code' => 'FIN', 'active' => true, 'status' => 'active'],
        );

        $reporterName = (string) $this->option('reporter');
        $reporter = User::query()->where('username', $reporterName)->first();
        if (! $reporter) {
            $this->error("Reporter not found: {$reporterName}");

            return self::FAILURE;
        }

        $deptHeadName = $this->option('dept-head');
        $itHead = $deptHeadName
            ? User::query()->where('username', $deptHeadName)->first()
            : User::query()->where('role', Roles::DEPT_HEAD)->where('department', 'Information Technology')->first()
                ?? User::query()->where('role', Roles::DEPT_HEAD)->where('active', true)->first();

        if (! $itHead) {
            $this->error('No dept_head user found. Run rms:import-users first.');

            return self::FAILURE;
        }

        $targetDept = (string) $this->option('target-dept');
        $targetHead = User::query()
            ->where('role', Roles::DEPT_HEAD)
            ->where('active', true)
            ->get()
            ->first(fn (User $u) => strcasecmp((string) $u->department, $targetDept) === 0);

        if (! $targetHead) {
            $this->error("No dept_head found for target department: {$targetDept}");

            return self::FAILURE;
        }

        $ticket = $drafts->create($reporter, [
            'title' => 'Slice6 dept workflow smoke',
            'what' => 'Workflow test',
            'why' => 'Smoke',
            'where' => 'HQ',
            'when' => 'Now',
            'who' => 'Ops',
            'how' => 'Test',
            'evidenceCount' => 1,
            'reporterDepartment' => $itHead->department,
        ]);
        $this->info("created {$ticket->reference}");

        $ticket = $submitter->submit($ticket, $reporter);
        $ticket->department = $itHead->department;
        $ownership = is_array($ticket->ownership) ? $ticket->ownership : [];
        $ownership['ownerDepartment'] = $itHead->department;
        $ticket->ownership = $ownership;
        $ticket->save();

        $ticket = $deptTickets->accept($ticket->fresh(), $itHead, ['comment' => 'smoke accept']);
        $this->info("accepted status={$ticket->status}");

        $ticket = $deptTickets->returnForRevision($ticket, $itHead, ['reason' => 'Add more evidence']);
        $this->info("returned status={$ticket->status}");

        $ticket->status = 'assigned';
        $ownership = is_array($ticket->ownership) ? $ticket->ownership : [];
        $ownership['state'] = 'pending';
        $ownership['ownerUsername'] = null;
        $ticket->ownership = $ownership;
        $ticket->save();

        $ticket = $deptTickets->reassign($ticket->fresh(), $itHead, [
            'reason' => 'Smoke reassignment',
            'comment' => 'Smoke reassignment comment',
            'targetDepartment' => $targetDept,
        ]);
        $this->info("reassigned status={$ticket->status} dept={$ticket->department}");

        $ticket = $deptTickets->accept($ticket->fresh(), $targetHead, ['comment' => 'target accept']);
        $ticket->status = 'pending_audit';
        $ticket->accomplishment_external_id = 'acc-smoke-s6';
        $ticket->save();
        $this->info('prepared for closure');

        $ticket = $deptTickets->close($ticket->fresh(), $targetHead, ['closingNotes' => 'Smoke closure']);
        $this->info("closed status={$ticket->status}");

        $ref = $ticket->reference;
        $ticket->delete();
        $this->info("cleaned up {$ref}");
        $this->line('Express store.json was not modified. USE_LARAVEL_API defaults ON (Phase 5); Express UI remains browser entry.');

        return self::SUCCESS;
    }
}
