<?php

namespace App\Http\Controllers;

use App\Services\AdminUserService;
use App\Services\ExpressOrgMirrorService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 5 slice 15 + Phase 7 slice 3: System Administrator user management (Blade GET + POST).
 */
class AdminUserController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly AdminUserService $users,
        private readonly ExpressOrgMirrorService $orgMirror,
    ) {}

    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function edit(Request $request, string $username): View|RedirectResponse
    {
        $payload = $this->users->list(
            $request->query('q'),
            $request->query('role'),
            $request->query('status'),
            $request->query('action'),
            $request->query('filter'),
            $username,
        );

        if ($payload['editUser'] === null) {
            return redirect()->away('/admin/users?flash=not_found');
        }

        return view('admin.users', $this->viewData($request, $payload));
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->users->create($request->all());
        if (! empty($result['error'])) {
            return redirect()->away('/admin/users?action=add&error='.rawurlencode((string) $result['error']));
        }

        $user = $result['user'];
        $this->orgMirror->syncUser('upsert', $this->mirrorPayload($user, $result['password'] ?? null), $this->audit(
            $request,
            'user_created',
            'Created account: '.$user['displayName'].' ('.$user['username'].')',
            $user['username'],
        ), [
            'type' => 'user_created',
            'title' => 'New user created',
            'message' => $user['displayName'].' was added to the system.',
        ], [
            'action' => 'account_created',
            'username' => $user['username'],
            'actor' => $request->user()->username,
            'detail' => 'Created account with role '.$user['roleLabel'],
            'success' => true,
        ]);

        return redirect()->away('/admin/users?flash=created');
    }

    public function update(Request $request, string $username): RedirectResponse
    {
        $result = $this->users->update($username, $request->all());
        if (! empty($result['error'])) {
            if ($result['error'] === 'User not found.') {
                return redirect()->away('/admin/users?flash=not_found');
            }

            return redirect()->away('/admin/users/'.rawurlencode(strtolower($username)).'/edit?error='.rawurlencode((string) $result['error']));
        }

        $user = $result['user'];
        $this->orgMirror->syncUser('upsert', $this->mirrorPayload($user), $this->audit(
            $request,
            'user_updated',
            'Updated account: '.$user['displayName'],
            $user['username'],
        ));

        return redirect()->away('/admin/users?flash=updated');
    }

    public function destroy(Request $request, string $username): RedirectResponse
    {
        $result = $this->users->delete($username);
        if (! empty($result['error'])) {
            return redirect()->away('/admin/users?error='.rawurlencode((string) $result['error']));
        }

        $user = $result['user'];
        $this->orgMirror->syncUser('delete', ['username' => $user['username']], $this->audit(
            $request,
            'user_deleted',
            'Deleted account: '.$user['displayName'],
            $user['username'],
        ), null, [
            'action' => 'account_deleted',
            'username' => $user['username'],
            'actor' => $request->user()->username,
            'detail' => 'Deleted account ('.$user['roleLabel'].')',
            'success' => true,
        ]);

        return redirect()->away('/admin/users?flash=deleted');
    }

    public function activate(Request $request, string $username): RedirectResponse
    {
        return $this->toggleStatus($request, $username, true);
    }

    public function deactivate(Request $request, string $username): RedirectResponse
    {
        return $this->toggleStatus($request, $username, false);
    }

    public function showResetPassword(Request $request, string $username): View|RedirectResponse
    {
        $target = $this->users->findIdentity($username);
        if ($target === null) {
            return redirect()->away('/admin/users?flash=not_found');
        }

        return view('admin.user-reset-password', [
            'user' => $request->user()->toIdentityArray(),
            'activeNav' => 'users',
            'title' => 'Reset Password',
            'targetUser' => $target,
            'flash' => $request->query('flash'),
            'error' => $request->query('error'),
        ]);
    }

    public function resetPassword(Request $request, string $username): RedirectResponse
    {
        if ($request->input('mode') === 'prompt') {
            return redirect()->away('/admin/users/'.rawurlencode(strtolower($username)).'/reset-password');
        }

        $result = $this->users->resetPassword($username, $request->all());
        if (! empty($result['error'])) {
            if ($result['error'] === 'User not found.') {
                return redirect()->away('/admin/users?flash=not_found');
            }

            return redirect()->away('/admin/users/'.rawurlencode(strtolower($username)).'/reset-password?error='.rawurlencode((string) $result['error']));
        }

        $user = $result['user'];
        $this->orgMirror->syncUser('upsert', $this->mirrorPayload($user, $result['password'] ?? null), $this->audit(
            $request,
            'password_reset',
            'Reset password for: '.$user['displayName'],
            $user['username'],
        ));

        return redirect()->away('/admin/users?flash=password_reset');
    }

    private function toggleStatus(Request $request, string $username, bool $active): RedirectResponse
    {
        $result = $this->users->setStatus($username, $active);
        if (! empty($result['error'])) {
            return redirect()->away('/admin/users?error='.rawurlencode((string) $result['error']));
        }

        $user = $result['user'];
        $action = $active ? 'user_activated' : 'user_deactivated';
        $this->orgMirror->syncUser('upsert', $this->mirrorPayload($user), $this->audit(
            $request,
            $action,
            ($active ? 'Activated' : 'Deactivated').' account: '.$user['displayName'],
            $user['username'],
        ));

        return redirect()->away('/admin/users?flash='.($active ? 'activated' : 'deactivated'));
    }

    private function render(Request $request): View
    {
        $payload = $this->users->list(
            $request->query('q'),
            $request->query('role'),
            $request->query('status'),
            $request->query('action'),
            $request->query('filter'),
        );

        return view('admin.users', $this->viewData($request, $payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function viewData(Request $request, array $payload): array
    {
        $user = $request->user();
        $allUsers = array_values($payload['users']);
        $total = count($allUsers);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        return [
            'user' => $user->toIdentityArray(),
            'activeNav' => 'users',
            'title' => 'User Management',
            'users' => array_slice($allUsers, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'pagination' => [
                'page' => $page,
                'lastPage' => $lastPage,
            ],
            'departments' => $payload['departments'],
            'roles' => $payload['roles'],
            'filters' => $payload['filters'],
            'editUser' => $payload['editUser'],
            'showForm' => $payload['showForm'],
            'flash' => $request->query('flash'),
            'error' => $request->query('error'),
        ];
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>
     */
    private function mirrorPayload(array $user, ?string $password = null): array
    {
        $payload = [
            'username' => $user['username'],
            'displayName' => $user['displayName'],
            'email' => $user['email'],
            'role' => $user['role'],
            'roleLabel' => $user['roleLabel'],
            'employeeId' => $user['employeeId'],
            'department' => $user['department'],
            'position' => $user['position'],
            'canManageUsers' => (bool) ($user['canManageUsers'] ?? false),
            'builtIn' => (bool) ($user['builtIn'] ?? false),
            'active' => (bool) ($user['active'] ?? true),
            'status' => $user['status'] ?? 'active',
            'deleted' => ($user['status'] ?? '') === 'deleted',
        ];
        if ($password !== null && $password !== '') {
            $payload['password'] = $password;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function audit(Request $request, string $action, string $description, ?string $targetUser = null): array
    {
        $user = $request->user();

        return [
            'username' => $user->username,
            'role' => $user->role,
            'roleLabel' => $user->role_label ?: Roles::label($user->role),
            'action' => $action,
            'module' => 'User Management',
            'description' => $description,
            'targetUser' => $targetUser,
            'ip' => $request->ip(),
        ];
    }
}
