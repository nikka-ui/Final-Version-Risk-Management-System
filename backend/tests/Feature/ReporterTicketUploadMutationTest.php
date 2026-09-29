<?php

namespace Tests\Feature;

use App\Models\RiskAttachment;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReporterTicketUploadMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_eight_slice_three(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_create_or_edit_with_upload(): void
    {
        $file = $this->fakePdf('evidence.pdf');

        $this->post('/supervisor/tickets/new/preview', $this->formPayload('RISK-TEST-U001', [
            'attachments' => [$file],
        ]))->assertRedirect();

        $this->post('/supervisor/tickets/RISK-TEST-U001/edit', $this->formPayload('RISK-TEST-U001', [
            'attachments' => [$file],
        ]))->assertRedirect();

        $this->assertFalse(RiskTicket::query()->where('reference', 'RISK-TEST-U001')->exists());
    }

    public function test_reporter_can_create_and_edit_with_uploads(): void
    {
        Storage::fake('evidence');

        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'department' => 'Information Technology',
        ]);

        $createFile = $this->fakePdf('create.pdf');
        $this->actingAs($reporter)
            ->post('/supervisor/tickets/new/preview', $this->formPayload('RISK-TEST-U002', [
                'attachments' => [$createFile],
            ]))
            ->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-U002')->first();
        $this->assertNotNull($ticket);
        $this->assertSame('draft', $ticket->status);
        $this->assertSame('Create upload RISK-TEST-U002', $ticket->title);
        $this->assertGreaterThanOrEqual(1, (int) $ticket->evidence_count);
        $this->assertSame(1, RiskAttachment::query()->where('ticket_ref', 'RISK-TEST-U002')->count());

        $editFile = $this->fakePng('edit.png');
        $this->actingAs($reporter)
            ->post('/supervisor/tickets/RISK-TEST-U002/edit', $this->formPayload('RISK-TEST-U002', [
                'title' => 'Edited upload RISK-TEST-U002',
                'what' => 'Updated what',
                'attachments' => [$editFile],
            ]))
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('Edited upload RISK-TEST-U002', $ticket->title);
        $this->assertSame('Updated what', $ticket->five_w1h['what'] ?? null);
        $this->assertGreaterThanOrEqual(2, (int) $ticket->evidence_count);
    }

    public function test_create_requires_evidence_file(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);

        $this->actingAs($reporter)
            ->post('/supervisor/tickets/new/preview', $this->formPayload('RISK-TEST-U003'))
            ->assertRedirect();

        $this->assertFalse(RiskTicket::query()->where('reference', 'RISK-TEST-U003')->exists());
    }

    public function test_non_reporter_cannot_create_upload(): void
    {
        Storage::fake('evidence');

        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
        ]);
        $file = $this->fakePdf('evidence.pdf');

        $this->actingAs($head)
            ->post('/supervisor/tickets/new/preview', $this->formPayload('RISK-TEST-U004', [
                'attachments' => [$file],
            ]))
            ->assertRedirect();

        $this->assertFalse(RiskTicket::query()->where('reference', 'RISK-TEST-U004')->exists());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formPayload(string $reference, array $overrides = []): array
    {
        return array_merge([
            'referenceOverride' => $reference,
            'title' => 'Create upload '.$reference,
            'location' => 'HQ',
            'what' => 'what',
            'why' => 'why',
            'where' => 'where',
            'when' => 'when',
            'who' => 'who',
            'how' => 'how',
        ], $overrides);
    }
}
