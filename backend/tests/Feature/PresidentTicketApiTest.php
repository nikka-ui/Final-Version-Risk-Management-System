<?php

namespace Tests\Feature;

use App\Models\RiskAttachment;
use App\Models\RiskTicket;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresidentTicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $username, string $password): string
    {
        return $this->postJson('/v1/auth/token', compact('username', 'password'))->json('token');
    }

    public function test_president_approves_action_plan(): void
    {
        User::factory()->create([
            'username' => 'reporter',
            'password' => 'a3c2026',
            'role' => Roles::SUPERVISOR,
            'department' => 'Information Technology',
        ]);
        User::factory()->create([
            'username' => 'president',
            'password' => 'pres2026',
            'role' => Roles::PRESIDENT,
            'name' => 'President',
        ]);

        $reporterToken = $this->token('reporter', 'a3c2026');
        $presidentToken = $this->token('president', 'pres2026');

        $reference = $this->withToken($reporterToken)
            ->postJson('/v1/tickets', [
                'title' => 'Critical infrastructure risk',
                'what' => 'Outage',
                'why' => 'SPOF',
                'where' => 'DC',
                'when' => 'Now',
                'who' => 'Ops',
                'how' => 'Failure',
                'evidenceCount' => 1,
            ])
            ->json('ticket.reference');

        RiskAttachment::query()->create([
            'id' => 'att-'.md5($reference),
            'ticket_ref' => $reference,
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'storage_key' => "{$reference}/evidence.pdf",
            'uploaded_by' => 'reporter',
            'legacy' => false,
            'uploaded_at' => now(),
        ]);

        $this->withToken($reporterToken)->postJson("/v1/tickets/{$reference}/submit")->assertOk();

        RiskTicket::query()->where('reference', $reference)->update([
            'status' => 'pending_president',
            'likelihood' => 5,
            'impact' => 5,
            'ai' => ['riskLevel' => ['id' => 'critical', 'label' => 'Critical']],
            'action_plan' => [
                'summary' => 'Emergency mitigation',
                'steps' => ['Replace hardware'],
                'submittedForReviewAt' => now()->toIso8601String(),
            ],
        ]);

        $this->withToken($reporterToken)
            ->postJson("/v1/tickets/{$reference}/president-decision", ['decision' => 'approve'])
            ->assertForbidden();

        $this->withToken($presidentToken)
            ->postJson("/v1/tickets/{$reference}/president-decision", ['decision' => 'approve', 'note' => 'Proceed'])
            ->assertOk()
            ->assertJsonPath('ticket.status', 'in_mitigation')
            ->assertJsonPath('ticket.presidentPlanDecision.decisionId', 'approve');
    }

    public function test_president_reject_requires_note(): void
    {
        User::factory()->create([
            'username' => 'president',
            'password' => 'pres2026',
            'role' => Roles::PRESIDENT,
        ]);

        $presidentToken = $this->token('president', 'pres2026');

        $ticket = RiskTicket::query()->create([
            'external_id' => 't-pres-1',
            'reference' => 'RISK-2026-99999',
            'title' => 'High risk ticket',
            'status' => 'pending_president',
            'department' => 'Finance',
            'submitted_by' => 'reporter',
            'likelihood' => 4,
            'impact' => 4,
            'ai' => ['riskLevel' => ['id' => 'high', 'label' => 'High']],
            'action_plan' => ['summary' => 'Plan'],
            'source_created_at' => now(),
            'source_updated_at' => now(),
        ]);

        $this->withToken($presidentToken)
            ->postJson("/v1/tickets/{$ticket->reference}/president-decision", ['decision' => 'reject'])
            ->assertStatus(422);

        $this->withToken($presidentToken)
            ->postJson("/v1/tickets/{$ticket->reference}/president-decision", [
                'decision' => 'reject',
                'note' => 'Insufficient detail',
            ])
            ->assertOk()
            ->assertJsonPath('ticket.status', 'in_progress')
            ->assertJsonPath('ticket.presidentPlanDecision.decisionId', 'reject');
    }
}
