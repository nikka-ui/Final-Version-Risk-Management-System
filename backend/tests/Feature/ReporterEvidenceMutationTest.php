<?php

namespace Tests\Feature;

use App\Models\Accomplishment;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReporterEvidenceMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_eight_slice_three(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_upload_evidence_or_accomplishment(): void
    {
        $file = $this->fakePdf('evidence.pdf');
        $this->reopenedTicket('RISK-TEST-E001', 'reporter1');

        $this->post('/supervisor/tickets/RISK-TEST-E001/evidence', [
            'attachments' => [$file],
        ])->assertRedirect();
        $this->post('/supervisor/tickets/RISK-TEST-E001/accomplishment', [
            'summary' => 's',
            'outcomes' => 'o',
            'attachments' => [$file],
        ])->assertRedirect();

        $this->assertSame(0, (int) RiskTicket::query()->where('reference', 'RISK-TEST-E001')->value('evidence_count'));
        $this->assertFalse(Accomplishment::query()->where('ticket_ref', 'RISK-TEST-E001')->exists());
    }

    public function test_reporter_can_add_evidence_on_reopened_ticket(): void
    {
        Storage::fake('evidence');
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);
        $this->reopenedTicket('RISK-TEST-E002', $reporter->username);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-E002/evidence', [
                'attachments' => [$this->fakePdf('reopen.pdf')],
            ])
            ->assertRedirect();

        $this->assertGreaterThanOrEqual(1, (int) RiskTicket::query()->where('reference', 'RISK-TEST-E002')->value('evidence_count'));
    }

    public function test_reporter_can_submit_accomplishment_with_proof(): void
    {
        Storage::fake('evidence');
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);
        $this->mitigationTicket('RISK-TEST-E003', $reporter->username);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-E003/accomplishment', [
                'summary' => 'Implemented the plan',
                'outcomes' => 'Risk reduced',
                'attachments' => [$this->fakePng('proof.png')],
            ])
            ->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-E003')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('pending_audit', $ticket->status);
        $this->assertNotEmpty($ticket->accomplishment_external_id);
        $this->assertTrue(Accomplishment::query()->where('ticket_ref', 'RISK-TEST-E003')->exists());
    }

    private function reopenedTicket(string $ref, string $username): RiskTicket
    {
        return RiskTicket::query()->create([
            'external_id' => 'ext-'.$ref,
            'reference' => $ref,
            'title' => $ref,
            'description' => $ref,
            'status' => 'reopened',
            'submitted_by' => $username,
            'evidence_count' => 0,
            'deleted' => false,
        ]);
    }

    private function mitigationTicket(string $ref, string $username): RiskTicket
    {
        $now = now();

        return RiskTicket::query()->create([
            'external_id' => 'ext-'.$ref,
            'reference' => $ref,
            'title' => $ref,
            'description' => $ref,
            'status' => 'in_mitigation',
            'submitted_by' => $username,
            'department' => 'Information Technology',
            'evidence_count' => 0,
            'deleted' => false,
            'mitigation_due_at' => $now->copy()->addDays(14),
            'ownership' => [
                'state' => 'accepted',
                'ownerUsername' => 'dept.head',
                'ownerDepartment' => 'Information Technology',
            ],
            'action_plan' => [
                'summary' => 'Fix the issue',
                'publishedToReporterAt' => $now->toIso8601String(),
            ],
            'payload' => ['officerNotes' => 'Implement the plan.'],
        ]);
    }
}
