<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeptTicketDetailController;
use App\Models\Department;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 7 slice 6: smoke Department Head Blade workflow POSTs.
 */
class SmokeSlice7DeptTicketMutations extends Command
{
    protected $signature = 'rms:smoke-slice7-dept-ticket-mutations';

    protected $description = 'Smoke Laravel dept accept/action-plan/return/reassign/close POSTs';

    public function handle(DeptTicketDetailController $controller): int
    {
        $it = Department::query()->firstOrCreate(
            ['code' => 'IT'],
            ['external_id' => 'dept-smoke-it', 'name' => 'Information Technology', 'active' => true, 'status' => 'active'],
        );
        $fin = Department::query()->firstOrCreate(
            ['code' => 'FIN'],
            ['external_id' => 'dept-smoke-fin', 'name' => 'Finance', 'active' => true, 'status' => 'active'],
        );

        $head = User::query()->create([
            'username' => 'smoke_dmut_'.bin2hex(random_bytes(2)),
            'name' => 'Smoke Dept Mutations',
            'email' => 'smoke_dmut_'.bin2hex(random_bytes(2)).'@rms.local',
            'password' => 'SmokeDmut1!',
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => $it->name,
            'position' => 'Department Head',
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $refA = 'RISK-SMOKE-'.strtoupper(bin2hex(random_bytes(3)));
        $refB = 'RISK-SMOKE-'.strtoupper(bin2hex(random_bytes(3)));
        $refC = 'RISK-SMOKE-'.strtoupper(bin2hex(random_bytes(3)));
        $ticketA = $this->assignedTicket($refA, $it->name, $head->username.'-a');
        $ticketB = $this->assignedTicket($refB, $it->name, $head->username.'-b');
        $ticketC = $this->assignedTicket($refC, $it->name, $head->username.'-c');

        Auth::login($head);
        try {
            $accept = $controller->accept($this->postRequest('/dept/tickets/'.$refA.'/accept', [
                'comment' => 'Smoke accept',
            ]), $refA);
            $ticketA->refresh();
            if ($ticketA->status !== 'in_progress' || ! str_contains($accept->getTargetUrl(), 'flash=ownership_accepted')) {
                $this->error('dept accept did not persist');

                return self::FAILURE;
            }
            $this->info('dept accept OK');

            $plan = $controller->saveActionPlan($this->postRequest('/dept/tickets/'.$refA.'/action-plan', [
                'summary' => 'Smoke action plan',
                'steps' => "Step one\nStep two",
            ]), $refA);
            $ticketA->refresh();
            $summary = is_array($ticketA->action_plan) ? ($ticketA->action_plan['summary'] ?? null) : null;
            if ($summary !== 'Smoke action plan' || ! str_contains($plan->getTargetUrl(), 'flash=action_plan_saved')) {
                $this->error('dept action-plan did not persist');

                return self::FAILURE;
            }
            $this->info('dept action-plan OK');

            $returned = $controller->returnForRevision($this->postRequest('/dept/tickets/'.$refA.'/return', [
                'reason' => 'Needs more evidence',
            ]), $refA);
            $ticketA->refresh();
            if ($ticketA->status !== 'returned' || ! str_contains($returned->getTargetUrl(), 'flash=report_returned')) {
                $this->error('dept return did not persist');

                return self::FAILURE;
            }
            $this->info('dept return OK');

            $reassign = $controller->reassign($this->postRequest('/dept/tickets/'.$refB.'/reassign', [
                'reason' => 'Finance owns this',
                'comment' => 'Smoke reassignment comment',
                'targetDepartment' => $fin->name,
            ]), $refB);
            $ticketB->refresh();
            if (
                $ticketB->department !== $fin->name
                || $ticketB->status !== 'assigned'
                || ! str_contains($reassign->getTargetUrl(), 'flash=ticket_reassigned')
            ) {
                $this->error('dept reassign did not persist');

                return self::FAILURE;
            }
            $this->info('dept reassign OK');

            $ticketC->status = 'pending_audit';
            $ticketC->accomplishment_external_id = 'acc-smoke-close';
            $ticketC->save();
            $closed = $controller->close($this->postRequest('/dept/tickets/'.$refC.'/close', [
                'comment' => 'Smoke close',
            ]), $refC);
            $ticketC->refresh();
            if ($ticketC->status !== 'closed' || ! str_contains($closed->getTargetUrl(), 'flash=ticket_closed_dept')) {
                $this->error('dept close did not persist');

                return self::FAILURE;
            }
            $this->info('dept close OK');
        } finally {
            Auth::logout();
            $ticketA->delete();
            $ticketB->delete();
            $ticketC->delete();
            $head->delete();
        }

        $this->info('cleanup OK');

        return self::SUCCESS;
    }

    private function assignedTicket(string $ref, string $department, string $ext): RiskTicket
    {
        return RiskTicket::query()->create([
            'external_id' => 'ext-'.$ext,
            'reference' => $ref,
            'title' => 'Smoke dept '.$ref,
            'status' => 'assigned',
            'department' => $department,
            'submitted_by' => 'reporter1',
            'submitted_by_name' => 'Reporter One',
            'deleted' => false,
            'ownership' => [
                'state' => 'pending',
                'ownerDepartment' => $department,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function postRequest(string $uri, array $input): Request
    {
        $request = Request::create($uri, 'POST', $input);
        $request->setUserResolver(fn () => Auth::user());

        return $request;
    }
}
