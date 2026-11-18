<x-auth-shell>
  <div class="login-otp">
    <div class="login-otp__badge" aria-hidden="true">
      <svg class="login-otp__lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <rect x="5" y="11" width="14" height="10" rx="2"/>
        <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
      </svg>
    </div>

    <h1 class="login-otp__title">Enter your code</h1>
    <p class="login-otp__sub">
      @if (!empty($maskedEmail))
        Enter the 6-digit code we emailed to {{ $maskedEmail }}.
      @else
        If an account exists for this username, we emailed a 6-digit code to the address on file.
      @endif
    </p>

    @if (!empty(session('status')))
      <div class="alert alert--success" role="status">{{ session('status') }}</div>
    @endif
    @if (!empty($error))
      <div class="alert" role="alert">{{ $error }}</div>
    @endif

    <form method="post" action="/login/otp" autocomplete="one-time-code" class="login-form login-otp__form" id="login-otp-form">
      @csrf
      @if (!empty($next))
        <input type="hidden" name="next" value="{{ $next }}">
      @endif
      <input type="hidden" name="otp" id="otp" value="" required>

      <fieldset class="login-otp__fieldset">
        <legend class="login-otp__legend">One-time code</legend>
        <div
          class="login-otp__digits{{ !empty($error) ? ' is-invalid' : '' }}"
          role="group"
          aria-label="6-digit verification code"
          data-otp-digits
        >
          @for ($i = 0; $i < 6; $i++)
            <input
              type="text"
              inputmode="numeric"
              pattern="[0-9]"
              maxlength="1"
              class="login-otp__digit{{ $i === 3 ? ' login-otp__digit--group-start' : '' }}"
              data-otp-digit
              data-index="{{ $i }}"
              aria-label="Digit {{ $i + 1 }} of 6"
              autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
              @if ($i === 0) autofocus @endif
            >
          @endfor
        </div>
      </fieldset>

      <button type="submit" class="login-submit" data-otp-submit disabled>Sign In</button>
    </form>

    <form method="post" action="/login/otp/resend" class="login-resend">
      @csrf
      <button type="submit" class="login-resend__btn">Resend code</button>
    </form>
    <p class="login-forgot"><a href="/login">Back to sign in</a></p>
  </div>
</x-auth-shell>
