<?php

namespace Tests\Feature;

use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeptDocumentMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_eight_slice_three(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_upload_dept_documents(): void
    {
        $this->inProgressTicket('RISK-TEST-DOC1', 'dept.head');
        $this->post('/dept/tickets/RISK-TEST-DOC1/documents', [
            'attachments' => [$this->fakePdf('doc.pdf')],
        ])->assertRedirect();

        $this->assertSame(0, (int) RiskTicket::query()->where('reference', 'RISK-TEST-DOC1')->value('evidence_count'));
    }

    public function test_dept_head_can_upload_documents(): void
    {
        Storage::fake('evidence');
        $head = User::factory()->create([
            'role' => Roles::DEPT_HEAD,
            'role_label' => Roles::label(Roles::DEPT_HEAD),
            'department' => 'Information Technology',
        ]);
        $this->inProgressTicket('RISK-TEST-DOC2', $head->username);

        $this->actingAs($head)
            ->post('/dept/tickets/RISK-TEST-DOC2/documents', [
                'attachments' => [$this->fakePdf('dept.pdf')],
            ])
            ->assertRedirect();

        $ticket = RiskTicket::query()->where('reference', 'RISK-TEST-DOC2')->first();
        $this->assertNotNull($ticket);
        $this->assertGreaterThanOrEqual(1, (int) $ticket->evidence_count);
        $this->assertNotEmpty($ticket->payload['implementationEvidenceIds'] ?? []);
    }

    private function inProgressTicket(string $ref, string $owner): RiskTicket
    {
        return RiskTicket::query()->create([
            'external_id' => 'ext-'.$ref,
            'reference' => $ref,
            'title' => $ref,
            'description' => $ref,
            'status' => 'in_progress',
            'submitted_by' => 'reporter1',
            'department' => 'Information Technology',
            'evidence_count' => 0,
            'deleted' => false,
            'ownership' => [
                'state' => 'accepted',
                'ownerUsername' => $owner,
                'ownerName' => 'Dept Head',
                'ownerDepartment' => 'Information Technology',
            ],
        ]);
    }
}
