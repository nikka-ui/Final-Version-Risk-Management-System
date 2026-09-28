<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_phase_seven_slice_three(): void
    {
        $this->getJson('/v1/health')
            ->assertOk()
            ->assertJsonPath('phase', 16)
            ->assertJsonPath('slice', 3);
    }

    public function test_user_list_is_paginated_ten_per_page(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(14)->create();

        $first = $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Page 1 of 2');
        $this->assertSame(10, substr_count($first->getContent(), 'data-label="Username"'));

        $second = $this->actingAs($admin)->get('/admin/users?page=2')
            ->assertOk()
            ->assertSee('Page 2 of 2');
        $this->assertSame(5, substr_count($second->getContent(), 'data-label="Username"'));

        $this->actingAs($admin)->get('/admin/users?page=99')
            ->assertOk()
            ->assertSee('Page 2 of 2');
    }

    public function test_guest_cannot_create_user(): void
    {
        $this->post('/admin/users', [
            'username' => 'analyst1',
            'displayName' => 'Analyst One',
            'email' => 'analyst1@rms.local',
            'department' => 'IT',
            'position' => 'Analyst',
            'role' => Roles::SUPERVISOR,
            'password' => 'Secret1!',
            'confirmPassword' => 'Secret1!',
        ])->assertRedirect();
    }

    public function test_admin_can_create_update_toggle_reset_and_delete_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'username' => 'analyst1',
                'displayName' => 'Analyst One',
                'email' => 'analyst1@rms.local',
                'department' => 'IT',
                'position' => 'Analyst',
                'role' => Roles::SUPERVISOR,
                'password' => 'Secret1!',
                'confirmPassword' => 'Secret1!',
            ])
            ->assertRedirect();

        $user = User::query()->where('username', 'analyst1')->first();
        $this->assertNotNull($user);
        $this->assertSame('Analyst One', $user->name);
        $this->assertTrue($user->active);

        $this->actingAs($admin)
            ->post('/admin/users/analyst1/edit', [
                'displayName' => 'Analyst One Updated',
                'email' => 'analyst1@rms.local',
                'employeeId' => $user->employee_id,
                'department' => 'IT',
                'position' => 'Senior Analyst',
                'role' => Roles::SUPERVISOR,
                'status' => 'active',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Analyst One Updated', $user->name);
        $this->assertSame('Senior Analyst', $user->position);

        $this->actingAs($admin)->post('/admin/users/analyst1/deactivate')->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->active);

        $this->actingAs($admin)->post('/admin/users/analyst1/activate')->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->active);

        $this->actingAs($admin)
            ->post('/admin/users/analyst1/reset-password', [
                'password' => 'Secret2!',
                'confirmPassword' => 'Secret2!',
            ])
            ->assertRedirect();

        $this->actingAs($admin)->post('/admin/users/analyst1/delete')->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->deleted);
        $this->assertSame('deleted', $user->status);
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $reporter = User::factory()->create([
            'role' => Roles::SUPERVISOR,
            'role_label' => Roles::label(Roles::SUPERVISOR),
        ]);

        $this->actingAs($reporter)
            ->post('/admin/users', [
                'username' => 'clerk1',
                'displayName' => 'Clerk',
                'email' => 'clerk1@rms.local',
                'department' => 'IT',
                'position' => 'Clerk',
                'role' => Roles::SUPERVISOR,
                'password' => 'Secret1!',
                'confirmPassword' => 'Secret1!',
            ])
            ->assertRedirect();

        $this->assertNull(User::query()->where('username', 'clerk1')->first());
    }
}
