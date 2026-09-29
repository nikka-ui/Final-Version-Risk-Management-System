<?php

namespace Tests\Feature;

use App\Models\RiskAttachment;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporterTicketMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_eight_slice_three(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_submit_or_delete_draft(): void
    {
        $this->draftTicket('RISK-TEST-R001', 'reporter1');

        $this->post('/supervisor/tickets/new/preview/RISK-TEST-R001/submit', [
            'confirmBox' => '1',
        ])->assertRedirect();
        $this->post('/supervisor/tickets/RISK-TEST-R001/delete')->assertRedirect();

        $this->assertSame('draft', RiskTicket::query()->where('reference', 'RISK-TEST-R001')->value('status'));
        $this->assertTrue(RiskTicket::query()->where('reference', 'RISK-TEST-R001')->exists());
    }

    public function test_reporter_can_save_submit_and_delete_draft(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'department' => 'Information Technology',
        ]);

        $ticketA = $this->draftTicket('RISK-TEST-R002', $reporter->username);
        $ticketB = $this->draftTicket('RISK-TEST-R003', $reporter->username);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/new/preview/RISK-TEST-R002/save')
            ->assertRedirect();
        $ticketA->refresh();
        $this->assertSame('draft', $ticketA->status);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/new/preview/RISK-TEST-R002/submit', [
                'confirmBox' => '1',
            ])
            ->assertRedirect();
        $ticketA->refresh();
        // Generic 5W1H text with no active departments cannot be auto-routed, so it waits for officer review.
        $this->assertSame('pending_ai_review', $ticketA->status);
        $this->assertSame('pending_ai_review', $ticketA->ownership['state'] ?? null);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-R003/delete')
            ->assertRedirect();
        $this->assertFalse(RiskTicket::query()->where('reference', 'RISK-TEST-R003')->exists());
    }

    public function test_submit_requires_confirmation(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);
        $ticket = $this->draftTicket('RISK-TEST-R004', $reporter->username);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/new/preview/RISK-TEST-R004/submit')
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('draft', $ticket->status);
    }

    public function test_non_reporter_cannot_submit_draft(): void
    {
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
        ]);
        $this->draftTicket('RISK-TEST-R005', 'reporter1');

        $this->actingAs($head)
            ->post('/supervisor/tickets/new/preview/RISK-TEST-R005/submit', [
                'confirmBox' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('draft', RiskTicket::query()->where('reference', 'RISK-TEST-R005')->value('status'));
    }

    public function test_reporter_can_comment_edit_and_react(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'department' => 'Information Technology',
        ]);
        $ticket = RiskTicket::query()->create([
            'external_id' => 'ext-RISK-TEST-R006',
            'reference' => 'RISK-TEST-R006',
            'title' => 'RISK-TEST-R006',
            'status' => 'assigned',
            'submitted_by' => $reporter->username,
            'department' => 'Information Technology',
            'deleted' => false,
            'thread_comments' => [],
        ]);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-R006/comment', [
                'comment' => 'Original reporter note',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $id = $ticket->thread_comments[0]['id'] ?? '';
        $this->assertSame('Original reporter note', $ticket->thread_comments[0]['body'] ?? null);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-R006/comment/edit', [
                'commentId' => $id,
                'comment' => 'Edited reporter note',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertSame('Edited reporter note', $ticket->thread_comments[0]['body'] ?? null);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-R006/comment/react', [
                'commentId' => $id,
                'reaction' => '🎉',
            ])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertContains($reporter->username, $ticket->thread_comments[0]['reactions']['🎉'] ?? []);
    }

    public function test_guest_cannot_post_reporter_comment_edit_or_react(): void
    {
        RiskTicket::query()->create([
            'external_id' => 'ext-RISK-TEST-R007',
            'reference' => 'RISK-TEST-R007',
            'title' => 'RISK-TEST-R007',
            'status' => 'assigned',
            'submitted_by' => 'reporter1',
            'department' => 'Information Technology',
            'deleted' => false,
            'thread_comments' => [],
        ]);

        $this->post('/supervisor/tickets/RISK-TEST-R007/comment', [
            'comment' => 'Guest note',
        ])->assertRedirect();
        $this->post('/supervisor/tickets/RISK-TEST-R007/comment/edit', [
            'commentId' => 'thr-x',
            'comment' => 'Hijack',
        ])->assertRedirect();
        $this->post('/supervisor/tickets/RISK-TEST-R007/comment/react', [
            'commentId' => 'thr-x',
            'reaction' => '🎉',
        ])->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-R007')->first();
        $this->assertSame([], $ticket?->thread_comments ?? []);
    }

    private function draftTicket(string $ref, string $username): RiskTicket
    {
        $ticket = RiskTicket::query()->create([
            'external_id' => 'ext-'.$ref,
            'reference' => $ref,
            'title' => $ref,
            'description' => $ref,
            'location' => 'HQ',
            'status' => 'draft',
            'submitted_by' => $username,
            'evidence_count' => 1,
            'deleted' => false,
            'five_w1h' => [
                'what' => 'what',
                'why' => 'why',
                'where' => 'where',
                'when' => 'when',
                'who' => 'who',
                'how' => 'how',
            ],
        ]);

        RiskAttachment::query()->create([
            'id' => 'att-'.md5($ref),
            'ticket_ref' => $ref,
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'storage_key' => "{$ref}/evidence.pdf",
            'uploaded_by' => $username,
            'legacy' => false,
            'uploaded_at' => now(),
        ]);

        return $ticket;
    }
}
