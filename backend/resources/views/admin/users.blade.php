@extends('layouts.admin')

@section('content')
  @php
    $flashLabels = [
      'created' => 'Account created successfully.',
      'updated' => 'Record updated successfully.',
      'deleted' => 'Record deleted successfully.',
      'activated' => 'User activated successfully.',
      'deactivated' => 'User deactivated successfully.',
      'password_reset' => 'Password reset successfully.',
      'not_found' => 'User not found.',
    ];
    $flashKey = is_string($flash ?? null) ? $flash : '';
    $flashMsg = $flashLabels[$flashKey] ?? null;
    $errorMsg = is_string($error ?? null) && $error !== '' ? $error : null;
    $filters = $filters ?? ['q' => '', 'role' => '', 'status' => '', 'action' => '', 'filter' => ''];
    $roles = $roles ?? [];
    $departments = $departments ?? [];
    $users = $users ?? [];
    $editUser = $editUser ?? null;
    $showForm = (bool) ($showForm ?? false);
    $pagination = $pagination ?? ['page' => 1, 'lastPage' => 1];
    $page = (int) $pagination['page'];
    $lastPage = (int) $pagination['lastPage'];
    $pageQuery = array_filter([
      'q' => $filters['q'] ?? '',
      'role' => $filters['role'] ?? '',
      'status' => $filters['status'] ?? '',
      'filter' => $filters['filter'] ?? '',
    ], static fn ($v) => $v !== '' && $v !== null);
    $pageUrl = static fn (int $p) => '/admin/users?'.http_build_query($pageQuery + ['page' => $p]);
  @endphp

  @if ($flashMsg)
    <div class="alert alert--ok" role="status">{{ $flashMsg }}</div>
  @endif
  @if ($errorMsg)
    <div class="alert alert--error" role="alert">{{ $errorMsg }}</div>
  @endif

  <div class="sup-page-head">
    <div>
      <h1>User Management</h1>
      <p class="sup-page-desc">Create, edit, activate, deactivate, and reset passwords for system users.</p>
    </div>
    <a href="/admin/users?action=add" class="sup-btn-primary">+ Add User</a>
  </div>

  <form method="get" action="/admin/users" class="admin-filter-bar">
    <input type="search" name="q" placeholder="Search users…" value="{{ $filters['q'] }}" aria-label="Search users">
    <select name="role" aria-label="Filter by role">
      <option value="">All roles</option>
      @foreach ($roles as $role)
        <option value="{{ $role['id'] }}" @selected($filters['role'] === $role['id'])>{{ $role['label'] }}</option>
      @endforeach
    </select>
    <select name="status" aria-label="Filter by status">
      <option value="">All statuses</option>
      <option value="active" @selected($filters['status'] === 'active')>Active</option>
      <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
    </select>
    <button type="submit" class="btn-outline">Filter</button>
  </form>

  @if ($showForm)
    @include('admin.partials.user-form', [
      'editUser' => $editUser,
      'departments' => $departments,
      'roles' => $roles,
    ])
  @endif

  <section class="sup-card sup-card--table admin-users-card">
    <div class="table-wrap">
      <table class="data-table data-table--compact sup-table admin-users-table">
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>Department</th>
            <th>Company Position</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $u)
            @php
              $isPrimaryAdmin = ($u['username'] ?? '') === 'admin';
              $status = $u['status'] ?? 'inactive';
              $statusCls = match ($status) {
                'active' => 'active',
                'deleted' => 'deleted',
                default => 'inactive',
              };
              $statusLabel = match ($status) {
                'active' => 'Active',
                'deleted' => 'Deleted',
                default => 'Inactive',
              };
            @endphp
            <tr>
              <td class="mono" data-label="Employee ID">{{ $u['employeeId'] ?: '—' }}</td>
              <td class="col-name" data-label="Full Name">
                <strong>{{ $u['displayName'] }}</strong>
                @if (!empty($u['builtIn']))
                  <span class="tag">built-in</span>
                @endif
              </td>
              <td class="col-email" data-label="Email">
                @if (!empty($u['email']) && str_contains($u['email'], '@'))
                  {{ \Illuminate\Support\Str::before($u['email'], '@') }}<wbr>{{ '@'.\Illuminate\Support\Str::after($u['email'], '@') }}
                @else
                  {{ $u['email'] ?: '—' }}
                @endif
              </td>
              <td class="mono" data-label="Username">{{ $u['username'] }}</td>
              <td data-label="Department">{{ $u['department'] ?: '—' }}</td>
              <td data-label="Company Position">{{ $u['position'] ?: '—' }}</td>
              <td data-label="Role">{{ $u['roleLabel'] }}</td>
              <td data-label="Status">
                <span class="admin-status admin-status--{{ $statusCls }}">
                  <span class="admin-status__dot" aria-hidden="true"></span>{{ $statusLabel }}
                </span>
              </td>
              <td class="col-actions" data-label="Actions">
                @if ($isPrimaryAdmin)
                  <span class="text-muted">Protected</span>
                @else
                  <div class="admin-action-cell">
                    <a href="/admin/users/{{ urlencode($u['username']) }}/edit"
                       class="admin-icon-btn admin-icon-btn--edit" title="Edit" aria-label="Edit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    </a>
                    <form method="post" action="/admin/users/{{ urlencode($u['username']) }}/reset-password" class="inline-form">
                      @csrf
                      <input type="hidden" name="mode" value="prompt">
                      <button type="submit" class="admin-icon-btn admin-icon-btn--reset" title="Reset Password" aria-label="Reset Password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3"/></svg>
                      </button>
                    </form>
                    @if (!empty($u['active']))
                      <form method="post" action="/admin/users/{{ urlencode($u['username']) }}/deactivate" class="inline-form">
                        @csrf
                        <button type="submit" class="admin-icon-btn admin-icon-btn--deactivate" title="Deactivate" aria-label="Deactivate">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        </button>
                      </form>
                    @else
                      <form method="post" action="/admin/users/{{ urlencode($u['username']) }}/activate" class="inline-form">
                        @csrf
                        <button type="submit" class="admin-icon-btn admin-icon-btn--activate" title="Activate" aria-label="Activate">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </button>
                      </form>
                    @endif
                    @if (empty($u['builtIn']))
                      <form method="post" action="/admin/users/{{ urlencode($u['username']) }}/delete" class="inline-form"
                            onsubmit="return confirm('Delete user {{ addslashes($u['displayName']) }}? This requires confirmation.');">
                        @csrf
                        <button type="submit" class="admin-icon-btn admin-icon-btn--delete" title="Delete" aria-label="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                      </form>
                    @endif
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="empty">No users found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <nav class="admin-pager-bar admin-pager" aria-label="User list pages">
      @if ($page > 1)
        <a href="{{ $pageUrl($page - 1) }}" class="admin-pager__btn" rel="prev" aria-label="Previous page">&lsaquo; Prev</a>
      @else
        <span class="admin-pager__btn is-disabled" aria-disabled="true">&lsaquo; Prev</span>
      @endif
      <span class="admin-pager__label" aria-current="page">Page {{ $page }} of {{ $lastPage }}</span>
      @if ($page < $lastPage)
        <a href="{{ $pageUrl($page + 1) }}" class="admin-pager__btn" rel="next" aria-label="Next page">Next &rsaquo;</a>
      @else
        <span class="admin-pager__btn is-disabled" aria-disabled="true">Next &rsaquo;</span>
      @endif
    </nav>
  </section>
@endsection
