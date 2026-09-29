@props([
    'title' => 'ACCC Risk Management System',
])
@php
  $brandDefaults = \App\Support\SystemSettings::defaults();
  try {
      $brandSettings = app(\App\Services\AdminSettingsService::class)->get();
  } catch (\Throwable $e) {
      // Sign-in must still render if the settings store is unreachable.
      $brandSettings = $brandDefaults;
  }
  $brand = static function (string $key) use ($brandSettings, $brandDefaults): string {
      $value = trim((string) ($brandSettings[$key] ?? ''));

      return $value !== '' ? $value : $brandDefaults[$key];
  };
  $brandTagline = $brand('landingTagline');
  $brandHeadline = $brand('landingHeadline');
  $brandOrg = $brand('organizationName');
  $brandFooter = strtr($brand('footerCopyright'), ['{YEAR}' => date('Y'), '{ORG}' => $brandOrg]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/css/app.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
  <div class="login-shell">
    <div class="login-card">
      <aside class="login-visual">
        <div class="login-visual__intro">
          <p class="login-visual__eyebrow">{{ $brandTagline }}</p>
          <h2 class="login-visual__headline">{{ $brandHeadline }}</h2>
        </div>
        <div class="login-visual__art">
          <img src="/img/risk-illustration.png" alt="Risk management dashboard illustration" class="login-visual__img">
        </div>
      </aside>
      <main class="login-panel">
        <div class="login-form-wrap">
          {{ $slot }}
        </div>
        <footer class="login-foot">
          <span>{{ $brandFooter }}</span>
          <span class="login-foot__credit">Developed by Nikka Shane T Cruz</span>
        </footer>
      </main>
    </div>
  </div>
  <script>
    (function () {
      document.querySelectorAll('.login-password-wrap').forEach(function (wrap) {
        const input = wrap.querySelector('input[type="password"], input[type="text"]');
        const toggle = wrap.querySelector('.login-password-toggle');
        if (!input || !toggle) return;
        toggle.addEventListener('click', function () {
          const show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          toggle.classList.toggle('is-visible', show);
          toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
          toggle.setAttribute('aria-pressed', String(show));
        });
      });

      const otpForm = document.getElementById('login-otp-form');
      if (otpForm) {
        const digits = Array.prototype.slice.call(otpForm.querySelectorAll('[data-otp-digit]'));
        const hidden = otpForm.querySelector('#otp');
        const statusEl = otpForm.querySelector('[data-otp-status]');
        const statusWrap = otpForm.querySelector('.login-otp__status');
        const submitBtn = otpForm.querySelector('[data-otp-submit]');

        function onlyDigit(value) {
          const match = String(value || '').match(/\d/);
          return match ? match[0] : '';
        }

        function codeValue() {
          return digits.map(function (el) { return onlyDigit(el.value); }).join('');
        }

        function updateStatus() {
          const filled = codeValue().length;
          const left = 6 - filled;
          if (hidden) hidden.value = codeValue();
          digits.forEach(function (el) {
            el.classList.toggle('is-filled', onlyDigit(el.value) !== '');
          });
          if (submitBtn) submitBtn.disabled = filled !== 6;
          if (statusWrap) statusWrap.classList.toggle('is-ready', left === 0);
          if (!statusEl) return;
          if (left === 0) statusEl.textContent = 'Ready';
          else if (left === 1) statusEl.textContent = '1 digit left';
          else statusEl.textContent = left + ' digits left';
        }

        function focusDigit(index) {
          const el = digits[Math.max(0, Math.min(index, digits.length - 1))];
          if (el) el.focus();
        }

        function setActive(index) {
          digits.forEach(function (el, i) {
            el.classList.toggle('is-active', i === index && onlyDigit(el.value) === '');
          });
        }

        function fillFrom(startIndex, text) {
          const chars = String(text || '').replace(/\D/g, '').slice(0, 6 - startIndex).split('');
          chars.forEach(function (ch, offset) {
            const el = digits[startIndex + offset];
            if (el) el.value = ch;
          });
          const next = Math.min(startIndex + chars.length, digits.length - 1);
          focusDigit(codeValue().length >= 6 ? digits.length - 1 : next);
          updateStatus();
        }

        digits.forEach(function (el, index) {
          el.addEventListener('focus', function () {
            el.select();
            setActive(index);
          });

          el.addEventListener('blur', function () {
            el.classList.remove('is-active');
          });

          el.addEventListener('input', function () {
            const raw = el.value;
            if (raw.length > 1) {
              fillFrom(index, raw);
              return;
            }
            el.value = onlyDigit(raw);
            updateStatus();
            if (el.value && index < digits.length - 1) focusDigit(index + 1);
            else setActive(index);
          });

          el.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace') {
              if (el.value) {
                el.value = '';
                updateStatus();
                setActive(index);
                event.preventDefault();
                return;
              }
              if (index > 0) {
                digits[index - 1].value = '';
                focusDigit(index - 1);
                updateStatus();
                setActive(index - 1);
                event.preventDefault();
              }
              return;
            }
            if (event.key === 'ArrowLeft' && index > 0) {
              focusDigit(index - 1);
              event.preventDefault();
              return;
            }
            if (event.key === 'ArrowRight' && index < digits.length - 1) {
              focusDigit(index + 1);
              event.preventDefault();
              return;
            }
          });

          el.addEventListener('paste', function (event) {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData).getData('text');
            fillFrom(index, text);
          });
        });

        otpForm.addEventListener('submit', function (event) {
          updateStatus();
          if (codeValue().length !== 6) {
            event.preventDefault();
            focusDigit(codeValue().length);
            return;
          }
          if (hidden) hidden.value = codeValue();
        });

        updateStatus();
        setActive(0);
      }
    })();
  </script>
</body>
</html>
