<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Support\SystemSettings;

/**
 * Phase 5 slice 21 + Phase 7 slice 4 + Phase 10 slice 2:
 * System Administrator settings (Blade GET + POST).
 * Postgres is the sole live read SoT; store.json dual-write is optional (off by default).
 */
class AdminSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $row = SystemSetting::query()->first();
        if ($row && is_array($row->payload)) {
            return SystemSettings::merge($row->payload);
        }

        return SystemSettings::defaults();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{settings: array<string, mixed>}
     */
    public function update(array $input): array
    {
        $merged = array_merge($this->get(), $this->normalizeForm($input));

        return ['settings' => $this->save($merged)];
    }

    /**
     * @return array{settings: array<string, mixed>}
     */
    public function resetLanding(): array
    {
        $defaults = SystemSettings::defaults();
        $merged = array_merge($this->get(), [
            'landingTagline' => $defaults['landingTagline'],
            'landingHeadline' => $defaults['landingHeadline'],
            'organizationName' => $defaults['organizationName'],
            'footerCopyright' => $defaults['footerCopyright'],
        ]);

        return ['settings' => $this->save($merged)];
    }

    /**
     * @return array{settings: array<string, mixed>}
     */
    public function resetAi(): array
    {
        $defaults = SystemSettings::defaults();
        $merged = array_merge($this->get(), [
            'defaultRiskLevels' => $defaults['defaultRiskLevels'],
        ]);

        return ['settings' => $this->save($merged)];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function save(array $settings): array
    {
        $merged = SystemSettings::merge($settings);
        $row = SystemSetting::query()->first();
        if ($row) {
            $row->payload = $merged;
            $row->save();
        } else {
            SystemSetting::query()->create(['payload' => $merged]);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeForm(array $input): array
    {
        $riskLevels = $this->stringList($input['defaultRiskLevels'] ?? null);
        $fileTypes = array_values(array_filter(array_map(
            fn (string $s) => strtolower($s),
            $this->stringList($input['allowedFileTypes'] ?? null),
        )));
        $freq = (string) ($input['backupFrequency'] ?? 'daily');
        if (! in_array($freq, ['daily', 'weekly'], true)) {
            $freq = 'daily';
        }
        $maxUpload = (int) ($input['maxUploadSizeMb'] ?? 0);

        $normalized = [
            'landingTagline' => mb_substr(trim((string) ($input['landingTagline'] ?? '')), 0, 120),
            'landingHeadline' => mb_substr(trim(str_replace("\r\n", "\n", (string) ($input['landingHeadline'] ?? ''))), 0, 200),
            'organizationName' => mb_substr(trim((string) ($input['organizationName'] ?? '')), 0, 80),
            'footerCopyright' => mb_substr(trim((string) ($input['footerCopyright'] ?? '')), 0, 160),
            'defaultRiskLevels' => $riskLevels,
            'maxUploadSizeMb' => $maxUpload > 0 ? $maxUpload : 25,
            'allowedFileTypes' => $fileTypes,
            'maintenanceMode' => $this->boolish($input['maintenanceMode'] ?? false),
            'backupEnabled' => $this->boolish($input['backupEnabled'] ?? false),
            'backupFrequency' => $freq,
        ];

        // Not on the admin Blade form; only overwrite when a client (e.g. the JSON API) sends them.
        if (array_key_exists('emailNotifications', $input)) {
            $normalized['emailNotifications'] = $this->boolish($input['emailNotifications']);
        }
        if (array_key_exists('mfaEnabled', $input)) {
            $normalized['mfaEnabled'] = $this->boolish($input['mfaEnabled']);
        }
        if (array_key_exists('passwordMinLength', $input)) {
            $passwordMin = (int) $input['passwordMinLength'];
            $normalized['passwordMinLength'] = $passwordMin > 0 ? $passwordMin : 8;
        }
        if (array_key_exists('sessionTimeoutMinutes', $input)) {
            $sessionTimeout = (int) $input['sessionTimeoutMinutes'];
            $normalized['sessionTimeoutMinutes'] = $sessionTimeout > 0 ? $sessionTimeout : 480;
        }

        return $normalized;
    }

    private function boolish(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = explode(',', (string) $value);
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $parts,
        ), static fn (string $item) => $item !== ''));
    }
}
