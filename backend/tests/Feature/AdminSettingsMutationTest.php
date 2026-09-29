<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_seven_slice_four(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_guest_cannot_save_settings(): void
    {
        $this->post('/admin/settings', [
            'organizationName' => 'Blocked',
            'landingTagline' => 'Nope',
        ])->assertRedirect();

        $this->assertNull(SystemSetting::query()->first());
    }

    public function test_admin_can_save_and_reset_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/settings', [
                'landingTagline' => 'Test tagline',
                'landingHeadline' => "Line one\nLine two",
                'organizationName' => 'Test Org',
                'defaultRiskLevels' => 'low, high',
                'emailNotifications' => '1',
                'passwordMinLength' => 10,
                'sessionTimeoutMinutes' => 60,
                'mfaEnabled' => '1',
                'maxUploadSizeMb' => 15,
                'allowedFileTypes' => 'pdf, PNG',
                'maintenanceMode' => '1',
                'backupEnabled' => '1',
                'backupFrequency' => 'weekly',
            ])
            ->assertRedirect();

        $row = SystemSetting::query()->first();
        $this->assertNotNull($row);
        $this->assertSame('Test Org', $row->payload['organizationName']);
        $this->assertSame('Test tagline', $row->payload['landingTagline']);
        $this->assertSame(['low', 'high'], $row->payload['defaultRiskLevels']);
        $this->assertSame(['pdf', 'png'], $row->payload['allowedFileTypes']);
        $this->assertTrue($row->payload['mfaEnabled']);
        $this->assertTrue($row->payload['maintenanceMode']);
        $this->assertSame('weekly', $row->payload['backupFrequency']);
        $this->assertSame(10, $row->payload['passwordMinLength']);

        $this->actingAs($admin)->post('/admin/settings/reset-landing')->assertRedirect();
        $row->refresh();
        $this->assertSame('ACCC', $row->payload['organizationName']);
        $this->assertSame('Identify. Assess. Mitigate.', $row->payload['landingTagline']);
        $this->assertTrue($row->payload['mfaEnabled']);

        $this->actingAs($admin)->post('/admin/settings/reset-ai')->assertRedirect();
        $row->refresh();
        $this->assertSame(['low', 'moderate', 'high', 'critical'], $row->payload['defaultRiskLevels']);
        $this->assertSame('weekly', $row->payload['backupFrequency']);
    }

    public function test_login_page_reflects_landing_settings(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Identify. Assess. Mitigate.')
            ->assertSee('ACCC. All rights reserved.');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post('/admin/settings', [
                'landingTagline' => 'Custom tagline',
                'landingHeadline' => "Custom\nHeadline",
                'organizationName' => 'Custom Org',
                'footerCopyright' => '© {YEAR} {ORG}. Internal use only.',
            ])
            ->assertRedirect();
        auth()->logout();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Custom tagline')
            ->assertSee("Custom\nHeadline", false)
            ->assertSee('© '.date('Y').' Custom Org. Internal use only.')
            ->assertDontSee('Identify. Assess. Mitigate.');
    }

    public function test_non_admin_cannot_save_settings(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);

        $this->actingAs($reporter)
            ->post('/admin/settings', [
                'organizationName' => 'Nope',
                'landingTagline' => 'Blocked',
            ])
            ->assertRedirect();

        $this->assertNull(SystemSetting::query()->first());
    }
}
