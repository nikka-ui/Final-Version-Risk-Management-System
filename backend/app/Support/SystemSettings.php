<?php

namespace App\Support;

/**
 * Default system settings — mirrors docker/web/config/admin.js DEFAULT_SYSTEM_SETTINGS.
 */
final class SystemSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'landingTagline' => 'Identify. Assess. Mitigate.',
            'landingHeadline' => "ACCC Risk\nManagement\nSystem",
            'organizationName' => 'ACCC',
            'footerCopyright' => '© {YEAR} {ORG}. All rights reserved.',
            'systemName' => 'AI-Assisted ISO 31000 Risk Management System',
            'themeColor' => '#2563eb',
            'defaultRiskLevels' => ['low', 'moderate', 'high', 'critical'],
            'ticketNumberFormat' => 'RISK-{YEAR}-{SEQ}',
            'emailNotifications' => true,
            'passwordMinLength' => 8,
            'passwordRequireUppercase' => true,
            'passwordRequireNumber' => true,
            'passwordRequireSpecial' => false,
            'maxUploadSizeMb' => 25,
            'allowedFileTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
            'maintenanceMode' => false,
            'backupEnabled' => true,
            'backupFrequency' => 'daily',
            'sessionTimeoutMinutes' => 480,
            'mfaEnabled' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public static function merge(array $stored): array
    {
        return array_merge(self::defaults(), $stored);
    }
}
