<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentUploadApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $username, string $password): string
    {
        return $this->postJson('/v1/auth/token', compact('username', 'password'))->json('token');
    }

    private function createTicket(string $token): string
    {
        return $this->withToken($token)
            ->postJson('/v1/tickets', [
                'title' => 'Upload slice10',
                'what' => 'Issue',
                'why' => 'Cause',
                'where' => 'HQ',
                'when' => 'Today',
                'who' => 'Team',
                'how' => 'Process',
                'evidenceCount' => 1,
            ])
            ->assertCreated()
            ->json('ticket.reference');
    }

    public function test_health_slice_thirteen(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_upload_and_download_roundtrip(): void
    {
        Storage::fake('evidence');

        User::factory()->create([
            'username' => 'reporter',
            'password' => 'a3c2026',
            'role' => Roles::SUPERVISOR,
            'department' => 'Information Technology',
        ]);
        $token = $this->token('reporter', 'a3c2026');
        $reference = $this->createTicket($token);

        $file = $this->fakePdf('evidence.pdf');

        $id = $this->withToken($token)
            ->post("/v1/tickets/{$reference}/attachments/upload", [
                'attachments' => [$file],
            ])
            ->assertCreated()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('evidenceCount', 1)
            ->assertJsonPath('attachments.0.originalName', 'evidence.pdf')
            ->json('attachments.0.id');

        $storageKey = "{$reference}/{$id}-evidence.pdf";
        Storage::disk('evidence')->assertExists($storageKey);

        $this->withToken($token)
            ->get("/v1/attachments/{$id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_upload_rejects_unsupported_type(): void
    {
        Storage::fake('evidence');

        User::factory()->create([
            'username' => 'reporter',
            'password' => 'a3c2026',
            'role' => Roles::SUPERVISOR,
        ]);
        $token = $this->token('reporter', 'a3c2026');
        $reference = $this->createTicket($token);

        $file = UploadedFile::fake()->create('malware.exe', 2, 'application/octet-stream');

        $this->withToken($token)
            ->post("/v1/tickets/{$reference}/attachments/upload", [
                'attachments' => [$file],
            ])
            ->assertStatus(422);
    }

    public function test_download_missing_returns_404(): void
    {
        Storage::fake('evidence');

        User::factory()->create([
            'username' => 'reporter',
            'password' => 'a3c2026',
            'role' => Roles::SUPERVISOR,
        ]);
        $token = $this->token('reporter', 'a3c2026');

        $this->withToken($token)
            ->getJson('/v1/attachments/att-does-not-exist/download')
            ->assertStatus(404);
    }
}
