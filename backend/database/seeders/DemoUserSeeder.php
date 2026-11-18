<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Roles;
use App\Support\UserSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Idempotent local/dev demo users (including compliance_officer).
 * Password: RMS_SEED_PASSWORD env, else leave existing users unchanged when empty.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('RMS_SEED_PASSWORD', '');
        if ($password === '') {
            $this->command?->warn('RMS_SEED_PASSWORD is empty — DemoUserSeeder will only create users when a password is provided.');
            $this->command?->warn('Tip: set RMS_SEED_PASSWORD for local seed, or run php artisan rms:import-users (merges UserSeed built-ins).');

            return;
        }

        $now = now()->toIso8601String();
        $created = 0;
        $updated = 0;

        foreach (UserSeed::users($now, $password) as $row) {
            $username = strtolower((string) $row['username']);
            $attributes = [
                'name' => (string) $row['displayName'],
                'email' => strtolower((string) $row['email']),
                'role' => (string) $row['role'],
                'role_label' => (string) ($row['roleLabel'] ?? Roles::label((string) $row['role'])),
                'employee_id' => (string) ($row['employeeId'] ?? ''),
                'department' => (string) ($row['department'] ?? ''),
                'position' => (string) ($row['position'] ?? ''),
                'can_manage_users' => (bool) ($row['canManageUsers'] ?? false),
                'built_in' => (bool) ($row['builtIn'] ?? true),
                'active' => true,
                'status' => 'active',
                'deleted' => false,
                'deleted_at' => null,
            ];

            // Seed emails/employee IDs are reserved; move collisions off other rows
            // so re-seed can reset passwords without unique-constraint failures.
            $email = $attributes['email'];
            $employeeId = $attributes['employee_id'];
            User::query()
                ->where('username', '!=', $username)
                ->where(function ($q) use ($email, $employeeId) {
                    $q->where('email', $email);
                    if ($employeeId !== '') {
                        $q->orWhere('employee_id', $employeeId);
                    }
                })
                ->get()
                ->each(function (User $conflict) use ($email, $employeeId): void {
                    if (strcasecmp((string) $conflict->email, $email) === 0) {
                        $conflict->email = strtolower((string) $conflict->username).'+legacy@rms.local';
                    }
                    if ($employeeId !== '' && (string) $conflict->employee_id === $employeeId) {
                        $conflict->employee_id = 'LEGACY-'.(string) $conflict->id;
                    }
                    $conflict->save();
                });

            $user = User::query()->where('username', $username)->first();
            if ($user) {
                if (! Hash::check($password, $user->password)) {
                    $attributes['password'] = $password;
                }
                $user->fill($attributes);
                $user->save();
                $updated++;
            } else {
                User::query()->create(array_merge($attributes, [
                    'username' => $username,
                    'password' => $password,
                ]));
                $created++;
            }
        }

        $this->command?->info("Demo users: created={$created} updated={$updated}");
    }
}
