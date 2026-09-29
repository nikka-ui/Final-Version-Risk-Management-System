<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeptTicketMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_eight_slice_four(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_accept_ticket(): void
    {
        $this->assignedTicket('RISK-TEST-D001');

        $this->post('/dept/tickets/RISK-TEST-D001/accept', [
            'comment' => 'Nope',
        ])->assertRedirect();

        $this->assertSame('assigned', RiskTicket::query()->where('reference', 'RISK-TEST-D001')->value('status'));
    }

    public function test_dept_head_can_accept_plan_return_reassign_and_close(): void
    {
        Department::query()->create([
            'external_id' => 'dept-it',
            'name' => 'Information Technology',
            'code' => 'IT',
            'active' => true,
            'status' => 'active',
        ]);
        Department::query()->create([
            'external_id' => 'dept-fin',
            'name' => 'Finance',
            'code' => 'FIN',
            'active' => true,
            'status' => 'active',
        ]);

        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
        ]);

        $ticketA = $this->assignedTicket('RISK-TEST-D002');
        $ticketB = $this->assignedTicket('RISK-TEST-D003');
        $ticketC = $this->assignedTicket('RISK-TEST-D004');

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D002/accept', ['comment' => 'Taking it'])
            ->assertRedirect();
        $ticketA->refresh();
        $this->assertSame('in_progress', $ticketA->status);
        $this->assertSame('accepted', $ticketA->ownership['state'] ?? null);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D002/action-plan', [
                'summary' => 'Fix the gap',
                'steps' => "One\nTwo",
            ])
            ->assertRedirect();
        $ticketA->refresh();
        $this->assertSame('Fix the gap', $ticketA->action_plan['summary'] ?? null);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D002/return', ['reason' => 'Needs evidence'])
            ->assertRedirect();
        $ticketA->refresh();
        $this->assertSame('returned', $ticketA->status);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D003/reassign', [
                'reason' => 'Finance owns this',
                'comment' => 'Handing off with context for Finance.',
                'targetDepartment' => 'Finance',
            ])
            ->assertRedirect();
        $ticketB->refresh();
        $this->assertSame('Finance', $ticketB->department);
        $this->assertSame('assigned', $ticketB->status);

        $ticketC->status = 'pending_audit';
        $ticketC->accomplishment_external_id = 'acc-test-1';
        $ticketC->save();

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D004/close', ['comment' => 'Reviewed'])
            ->assertRedirect();
        $ticketC->refresh();
        $this->assertSame('closed', $ticketC->status);
        $this->assertSame('Reviewed', $ticketC->closure['notes'] ?? null);
    }

    public function test_guest_cannot_post_dept_comment(): void
    {
        $this->assignedTicket('RISK-TEST-D006');

        $this->post('/dept/tickets/RISK-TEST-D006/comment', [
            'comment' => 'Guest note',
        ])->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-D006')->first();
        $this->assertSame([], $ticket?->thread_comments ?? []);
    }

    public function test_dept_head_can_post_comment_and_reply(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
        ]);
        $ticket = $this->assignedTicket('RISK-TEST-D007');

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D007/comment', [
                'comment' => 'Dept note',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $thread = $ticket->thread_comments ?? [];
        $this->assertCount(1, $thread);
        $this->assertSame('Dept note', $thread[0]['body'] ?? null);
        $this->assertSame(Roles::DEPT_HEAD, $thread[0]['authorRole'] ?? null);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D007/comment', [
                'comment' => 'Follow-up',
                'parentId' => $thread[0]['id'],
            ])
            ->assertRedirect();

        $ticket->refresh();
        $thread = $ticket->thread_comments ?? [];
        $this->assertCount(2, $thread);
        $this->assertSame($thread[0]['id'], $thread[1]['parentId'] ?? null);
        $this->assertSame('Follow-up', $thread[1]['body'] ?? null);
    }

    public function test_dept_head_empty_comment_is_rejected(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
        ]);
        $ticket = $this->assignedTicket('RISK-TEST-D008');

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D008/comment', [
                'comment' => '   ',
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame([], $ticket->thread_comments ?? []);
    }

    public function test_other_department_cannot_post_comment(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Finance',
        ]);
        $this->assignedTicket('RISK-TEST-D009');

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D009/comment', [
                'comment' => 'Wrong dept',
            ])
            ->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-D009')->first();
        $this->assertSame([], $ticket?->thread_comments ?? []);
    }

    public function test_dept_head_can_edit_own_comment_and_toggle_reaction(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
        ]);
        $ticket = $this->assignedTicket('RISK-TEST-D010');

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D010/comment', [
                'comment' => 'Original note',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $id = $ticket->thread_comments[0]['id'] ?? '';

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D010/comment/edit', [
                'commentId' => $id,
                'comment' => 'Edited note',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertSame('Edited note', $ticket->thread_comments[0]['body'] ?? null);
        $this->assertNotEmpty($ticket->thread_comments[0]['editedAt'] ?? null);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D010/comment/react', [
                'commentId' => $id,
                'reaction' => '👍',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertContains($head->username, $ticket->thread_comments[0]['reactions']['👍'] ?? []);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D010/comment/react', [
                'commentId' => $id,
                'reaction' => '👍',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertSame([], $ticket->thread_comments[0]['reactions']['👍'] ?? []);
    }

    public function test_guest_cannot_edit_or_react_to_comment(): void
    {
        $this->assignedTicket('RISK-TEST-D011');

        $this->post('/dept/tickets/RISK-TEST-D011/comment/edit', [
            'commentId' => 'thr-x',
            'comment' => 'Hijack',
        ])->assertRedirect();
        $this->post('/dept/tickets/RISK-TEST-D011/comment/react', [
            'commentId' => 'thr-x',
            'reaction' => '👍',
        ])->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-D011')->first();
        $this->assertSame([], $ticket?->thread_comments ?? []);
    }

    public function test_non_dept_head_cannot_accept_ticket(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'department' => 'Information Technology',
        ]);
        $this->assignedTicket('RISK-TEST-D005');

        $this->actingAs($reporter)
            ->post('/dept/tickets/RISK-TEST-D005/accept')
            ->assertRedirect();

        $this->assertSame('assigned', RiskTicket::query()->where('reference', 'RISK-TEST-D005')->value('status'));
    }

    public function test_guest_cannot_post_personnel_or_resolution(): void
    {
        $this->assignedTicket('RISK-TEST-D007');

        $this->post('/dept/tickets/RISK-TEST-D007/personnel', [
            'personName' => 'Alex Tech',
        ])->assertRedirect();
        $this->post('/dept/tickets/RISK-TEST-D007/resolution', [
            'closingNotes' => 'Nope',
        ])->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-D007')->first();
        $this->assertSame([], $ticket?->personnel ?? []);
        $this->assertSame('assigned', $ticket?->status);
    }

    public function test_dept_head_can_assign_personnel_and_submit_resolution(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
            'username' => 'dept.head.p8s4',
            'name' => 'Dept Head',
        ]);

        $inProgress = RiskTicket::query()->create([
            'external_id' => 'ext-RISK-TEST-D008',
            'reference' => 'RISK-TEST-D008',
            'title' => 'RISK-TEST-D008',
            'status' => 'in_progress',
            'department' => 'Information Technology',
            'submitted_by' => 'reporter1',
            'deleted' => false,
            'personnel' => [],
            'ownership' => [
                'state' => 'accepted',
                'ownerUsername' => $head->username,
                'ownerName' => $head->name,
                'ownerDepartment' => 'Information Technology',
            ],
        ]);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D008/personnel', [
                'personName' => 'Alex Tech',
                'personRole' => 'Implementer',
            ])
            ->assertRedirect();
        $inProgress->refresh();
        $this->assertSame('Alex Tech', $inProgress->personnel[0]['name'] ?? null);
        $this->assertSame('Implementer', $inProgress->personnel[0]['role'] ?? null);

        $pending = RiskTicket::query()->create([
            'external_id' => 'ext-RISK-TEST-D009',
            'reference' => 'RISK-TEST-D009',
            'title' => 'RISK-TEST-D009',
            'status' => 'pending_audit',
            'department' => 'Information Technology',
            'submitted_by' => 'reporter1',
            'accomplishment_external_id' => 'acc-test-9',
            'deleted' => false,
            'ownership' => [
                'state' => 'accepted',
                'ownerUsername' => $head->username,
                'ownerName' => $head->name,
                'ownerDepartment' => 'Information Technology',
            ],
        ]);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-D009/resolution', [
                'closingNotes' => 'Reviewed via resolution',
            ])
            ->assertRedirect();
        $pending->refresh();
        $this->assertSame('closed', $pending->status);
        $this->assertSame('Reviewed via resolution', $pending->closure['notes'] ?? null);
    }

    private function assignedTicket(string $ref): RiskTicket
    {
        return RiskTicket::query()->create([
            'external_id' => 'ext-'.$ref,
            'reference' => $ref,
            'title' => $ref,
            'status' => 'assigned',
            'department' => 'Information Technology',
            'submitted_by' => 'reporter1',
            'deleted' => false,
            'ownership' => [
                'state' => 'pending',
                'ownerDepartment' => 'Information Technology',
            ],
        ]);
    }
}
