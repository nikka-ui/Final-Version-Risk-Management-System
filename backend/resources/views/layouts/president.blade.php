<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }} — RMS</title>
  <link rel="icon" type="image/png" href="/img/favicon.png">
  <link rel="apple-touch-icon" href="/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/app.css?v=20261118-w1h">
</head>
<body class="supervisor-shell executive-console president-console">
  @php
    $displayName = $user['displayName'] ?? $user['username'] ?? 'User';
    $initial = mb_strtoupper(mb_substr(trim((string) $displayName), 0, 1));
    $positionLine = $user['position'] ?: 'President';
    $stats = $stats ?? [];
    $pendingCount = (int) ($stats['pendingCount'] ?? 0);
    $highCount = (int) ($stats['highCount'] ?? 0);
    $criticalCount = (int) ($stats['criticalCount'] ?? 0);
    $nav = [
      ['id' => 'overview', 'href' => '/president', 'label' => 'Dashboard'],
      ['id' => 'pending', 'href' => '/president/pending', 'label' => 'Pending decisions', 'statKey' => 'pendingCount'],
      ['id' => 'high', 'href' => '/president/high', 'label' => 'High risks', 'statKey' => 'highCount'],
      ['id' => 'critical', 'href' => '/president/critical', 'label' => 'Critical risks', 'statKey' => 'criticalCount'],
      ['id' => 'trends', 'href' => '/president/trends', 'label' => 'Trends'],
    ];
  @endphp
  <aside class="supervisor-sidebar">
    <div class="supervisor-sidebar__brand">
      <div class="supervisor-sidebar__logo" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="32" height="32" rx="8" fill="#7c3aed"/>
          <path d="M16 7L23 11V17C23 21.5 19.5 24.5 16 26C12.5 24.5 9 21.5 9 17V11L16 7Z" stroke="#fff" stroke-width="1.75" stroke-linejoin="round"/>
          <path d="M12 16L15 19L20 14" stroke="#fff" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="supervisor-sidebar__titles">
        <span class="supervisor-sidebar__system">Risk Management</span>
        <span class="supervisor-sidebar__role">President</span>
      </div>
    </div>
    <p class="supervisor-sidebar__section">Menu</p>
    <nav class="supervisor-sidebar__nav" aria-label="President navigation">
      @foreach ($nav as $item)
        @php
          $countKey = $item['statKey'] ?? null;
          $count = $countKey ? (int) ($stats[$countKey] ?? 0) : 0;
        @endphp
        <a href="{{ $item['href'] }}"
           class="supervisor-sidebar__link{{ ($activeNav ?? '') === $item['id'] ? ' supervisor-sidebar__link--active' : '' }}">
          <span class="supervisor-sidebar__label">{{ $item['label'] }}</span>
          @if ($count > 0)
            <span class="supervisor-sidebar__badge" aria-label="{{ $count }} pending">{{ $count }}</span>
          @endif
        </a>
      @endforeach
    </nav>
    <div class="supervisor-sidebar__user">
      <span class="supervisor-sidebar__avatar" aria-hidden="true">{{ $initial }}</span>
      <div class="supervisor-sidebar__user-meta">
        <span class="supervisor-sidebar__user-name">{{ $displayName }}</span>
        <span class="supervisor-sidebar__user-email">{{ $positionLine }}</span>
      </div>
    </div>
    <form class="supervisor-sidebar__logout" method="post" action="/logout">
      @csrf
      <button type="submit" class="supervisor-sidebar__signout">Sign out</button>
    </form>
  </aside>
  <div class="supervisor-content">
    <header class="console-topbar" aria-label="Page toolbar">
      <div class="console-topbar__title">{{ $title }}</div>
      <div class="console-topbar__actions">
        <span class="console-topbar__role-pill console-topbar__role-pill--executive">President</span>
        <form method="post" action="/president/notifications/read-all" class="notif-panel__mark-all">
          @csrf
          <button type="submit" class="btn-text">Mark all read</button>
        </form>
      </div>
    </header>
    <main class="supervisor-main">
      @yield('content')
    </main>
  </div>
</body>
</html>

