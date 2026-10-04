<?php

namespace Tests\Feature;

use Tests\TestCase;

class Phase12CiTest extends TestCase
{
    public function test_health_reports_phase_thirteen_slice_one(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_ci_workflow_includes_trivy_image_scan(): void
    {
        $candidates = [
            dirname(base_path()).DIRECTORY_SEPARATOR.'.github'.DIRECTORY_SEPARATOR.'workflows'.DIRECTORY_SEPARATOR.'ci.yml',
            base_path('.github'.DIRECTORY_SEPARATOR.'workflows'.DIRECTORY_SEPARATOR.'ci.yml'),
        ];
        $path = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if ($path === null) {
            $this->markTestSkipped('CI workflow is not mounted in this test environment.');
        }

        $yaml = strtolower((string) file_get_contents($path));
        $this->assertStringContainsString('trivy', $yaml);
        $this->assertStringContainsString('rms-api:ci', $yaml);
        $this->assertStringContainsString('rms-ai-service:ci', $yaml);
        $this->assertMatchesRegularExpression('/nginx:\d+\.\d+-alpine/', $yaml);
        $this->assertStringContainsString('severity: critical', $yaml);
    }
}
