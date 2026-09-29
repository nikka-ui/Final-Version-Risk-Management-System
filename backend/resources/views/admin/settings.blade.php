@extends('layouts.admin')

@section('content')
  @php
    $flashMsg = match (is_string($flash ?? null) ? $flash : '') {
      'settings_saved' => 'System settings saved successfully.',
      'landing_reset' => 'Landing page text restored to system defaults.',
      'ai_reset' => 'AI configuration restored to system defaults.',
      default => null,
    };
    $errorMsg = is_string($error ?? null) && $error !== '' ? urldecode($error) : null;
    $s = $settings ?? [];
    $fileTypes = implode(', ', is_array($s['allowedFileTypes'] ?? null) ? $s['allowedFileTypes'] : []);
    $riskLevels = implode(', ', is_array($s['defaultRiskLevels'] ?? null) ? $s['defaultRiskLevels'] : []);
  @endphp

  @if ($flashMsg)
    <div class="alert alert--ok" role="status">{{ $flashMsg }}</div>
  @endif
  @if ($errorMsg)
    <div class="alert alert--error" role="alert">{{ $errorMsg }}</div>
  @endif

  <div class="sup-page-head">
    <div>
      <h1>System Settings</h1>
      <p class="sup-page-desc">Configure landing page text, AI, file upload, and backup settings.</p>
    </div>
  </div>

  <form method="post" action="/admin/settings" class="admin-settings-form">
    @csrf
    <section class="sup-card sup-card--compact">
      <div class="admin-settings-section-head">
        <h2>Landing Page Title</h2>
        <button type="submit" class="admin-btn-reset" formaction="/admin/settings/reset-landing"
          onclick="return confirm('Reset landing page text to the system default configuration?');">
          Reset to default
        </button>
      </div>
      <p class="sup-muted-block">Edit the sign-in page text only. The illustration image is fixed.</p>
      <div class="admin-form-grid">
        <div class="field admin-form-grid__full">
          <label for="landingTagline">Tagline</label>
          <input id="landingTagline" name="landingTagline" type="text" maxlength="120"
            value="{{ $s['landingTagline'] ?? '' }}"
            placeholder="Identify. Assess. Mitigate.">
        </div>
        <div class="field admin-form-grid__full">
          <label for="landingHeadline">Headline</label>
          <textarea id="landingHeadline" name="landingHeadline" rows="3" maxlength="200"
            placeholder="ACCC Risk&#10;Management&#10;System">{{ $s['landingHeadline'] ?? '' }}</textarea>
          <span class="field-hint">Use a new line for each line shown on the landing page.</span>
        </div>
        <div class="field">
          <label for="organizationName">Organization Name</label>
          <input id="organizationName" name="organizationName" type="text" maxlength="80"
            value="{{ $s['organizationName'] ?? '' }}"
            placeholder="ACCC">
          <span class="field-hint">Shown in the sign-in page footer.</span>
        </div>
        <div class="field admin-form-grid__full">
          <label for="footerCopyright">Footer Copyright Text</label>
          <input id="footerCopyright" name="footerCopyright" type="text" maxlength="160"
            value="{{ $s['footerCopyright'] ?? '' }}"
            placeholder="© {YEAR} {ORG}. All rights reserved.">
          <span class="field-hint">Use {YEAR} for the current year and {ORG} for the organization name.</span>
        </div>
      </div>
    </section>

    <section class="sup-card sup-card--compact">
      <div class="admin-settings-section-head">
        <h2>AI Configuration</h2>
        <button type="submit" class="admin-btn-reset" formaction="/admin/settings/reset-ai"
          onclick="return confirm('Reset AI configuration to the system default risk levels?');">
          Reset to default
        </button>
      </div>
      <div class="field">
        <label for="defaultRiskLevels">Default Risk Levels</label>
        <input id="defaultRiskLevels" name="defaultRiskLevels" type="text" value="{{ $riskLevels }}">
      </div>
    </section>

    <section class="sup-card sup-card--compact">
      <h2>File Upload &amp; Maintenance</h2>
      <div class="admin-form-grid">
        <div class="field">
          <label for="maxUploadSizeMb">Max Upload Size (MB)</label>
          <input id="maxUploadSizeMb" name="maxUploadSizeMb" type="number"
            value="{{ $s['maxUploadSizeMb'] ?? 25 }}">
        </div>
        <div class="field admin-form-grid__full">
          <label for="allowedFileTypes">Allowed File Types</label>
          <input id="allowedFileTypes" name="allowedFileTypes" type="text" value="{{ $fileTypes }}">
        </div>
        <label class="admin-check-label">
          <input type="checkbox" name="maintenanceMode" value="1" @checked(!empty($s['maintenanceMode']))>
          Maintenance Mode
        </label>
        <label class="admin-check-label">
          <input type="checkbox" name="backupEnabled" value="1" @checked(!empty($s['backupEnabled']))>
          Backup Enabled
        </label>
        <div class="field">
          <label for="backupFrequency">Backup Frequency</label>
          <select id="backupFrequency" name="backupFrequency">
            <option value="daily" @selected(($s['backupFrequency'] ?? 'daily') === 'daily')>Daily</option>
            <option value="weekly" @selected(($s['backupFrequency'] ?? '') === 'weekly')>Weekly</option>
          </select>
        </div>
      </div>
    </section>

    <button type="submit" class="sup-btn-primary">Save Settings</button>
  </form>
@endsection
