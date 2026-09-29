<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_username_only(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Enter your username')
            ->assertSee('Continue')
            ->assertDontSee('name="password"', false)
            ->assertSee('Forgot password?')
            ->assertSee('/forgot-password')
            ->assertSee('Login as Admin')
            ->assertSee('/login?as=admin', false)
            ->assertSee('name="_token"', false);
    }

    public function test_admin_login_page_shows_password_form(): void
    {
        $this->get('/login?as=admin')
            ->assertOk()
            ->assertSee('Admin sign-in with username and password')
            ->assertSee('name="password"', false)
            ->assertSee('name="mode"', false)
            ->assertSee('value="password"', false)
            ->assertSee('Sign In')
            ->assertSee('value="admin"', false)
            ->assertSee('Sign in with one-time code')
            ->assertDontSee('Login as Admin');
    }

    public function test_admin_password_login_establishes_session_without_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@rms.local',
            'password' => 'admin-secret',
            'role' => Roles::ADMIN,
            'role_label' => Roles::label(Roles::ADMIN),
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin-secret',
            'mode' => 'password',
            'next' => '/admin',
        ]);

        $response->assertRedirect();
        $target = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/auth/bridge?code=', $target);
        $this->assertStringContainsString('next=%2Fadmin', $target);
        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_default_login_post_without_mode_still_requests_otp(): void
    {
        Mail::fake();

        User::factory()->create([
            'username' => 'reporter',
            'email' => 'reporter@rms.local',
            'password' => 'unused-for-web-login',
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $this->post('/login', [
            'username' => 'reporter',
            'password' => 'should-be-ignored',
        ])->assertRedirect('/login/otp');

        Mail::assertSent(LoginOtpMail::class, 1);
        $this->assertGuest();
    }

    public function test_otp_is_emailed_from_otp_service_and_logs_in(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'reporter',
            'email' => 'reporter@rms.local',
            'password' => 'unused-for-web-login',
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $this->post('/login', ['username' => 'reporter'])
            ->assertRedirect('/login/otp');

        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->hasFrom((string) config('mail.otp_from.address'))
                && strlen($mail->otp) === 6;
        });

        $otp = '';
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->withSession(['login_otp_username' => 'reporter'])
            ->get('/login/otp')
            ->assertOk()
            ->assertSee('r***@rms.local', false)
            ->assertSee('name="_token"', false);

        $response = $this->withSession([
            'login_otp_username' => 'reporter',
            'login_otp_next' => '/supervisor',
        ])->post('/login/otp', [
            'otp' => $otp,
            'next' => '/supervisor',
        ]);

        $response->assertRedirect();
        $target = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/auth/bridge?code=', $target);
        $this->assertStringContainsString('next=%2Fsupervisor', $target);
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_username_still_redirects_to_otp_without_mail(): void
    {
        Mail::fake();

        $this->post('/login', ['username' => 'nobody'])
            ->assertRedirect('/login/otp');

        Mail::assertNothingSent();

        $this->withSession(['login_otp_username' => 'nobody'])
            ->get('/login/otp')
            ->assertOk()
            ->assertSee('If an account exists', false);
    }

    public function test_wrong_otp_fails(): void
    {
        Mail::fake();

        User::factory()->create([
            'username' => 'reporter',
            'email' => 'reporter@rms.local',
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $this->post('/login', ['username' => 'reporter'])
            ->assertRedirect('/login/otp');

        $this->withSession(['login_otp_username' => 'reporter'])
            ->from('/login/otp')
            ->post('/login/otp', ['otp' => '000000'])
            ->assertRedirect('/login/otp');

        $this->assertGuest();
    }

    public function test_resend_otp_sends_another_mail(): void
    {
        Mail::fake();

        User::factory()->create([
            'username' => 'reporter',
            'email' => 'reporter@rms.local',
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
            'active' => true,
            'status' => 'active',
            'deleted' => false,
        ]);

        $this->post('/login', ['username' => 'reporter']);
        Mail::assertSent(LoginOtpMail::class, 1);

        $this->withSession(['login_otp_username' => 'reporter'])
            ->post('/login/otp/resend')
            ->assertRedirect('/login/otp');

        Mail::assertSent(LoginOtpMail::class, 2);
    }
}
