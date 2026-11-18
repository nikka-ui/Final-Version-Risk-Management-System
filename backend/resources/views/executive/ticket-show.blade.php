@extends('layouts.executive')

@section('content')
  @php
    $t = $ticket ?? [];
    $w = $fiveW1H ?? [];
    $ref = $t['reference'] ?? '';
    $level = $t['riskLevel'] ?? 'low';
    $comments = $threadComments ?? [];
    $tops = array_values(array_filter($comments, fn ($c) => empty($c['parentId'])));
    $childrenOf = function (string $parentId) use ($comments) {
      return array_values(array_filter($comments, fn ($c) => ($c['parentId'] ?? null) === $parentId));
    };
    $flashMsg = is_string($flash ?? null) && $flash !== '' ? $flash : null;
    $errorMsg = is_string($error ?? null) && $error !== '' ? urldecode($error) : null;
    $isOverdue = (bool) ($t['isOverdue'] ?? false);
    $tone = $isOverdue ? 'bad' : 'ok';
  @endphp

  @if ($flashMsg)
    <div class="alert alert--ok" role="status">{{ $flashMsg }}</div>
  @endif
  @if ($errorMsg)
    <div class="alert alert--error" role="alert">{{ $errorMsg }}</div>
  @endif

  <div class="sup-page-head">
    <div>
      <h1>{{ $t['title'] ?? 'Ticket' }}</h1>
      <p class="sup-page-desc mono">
        {{ $ref }} ·
        <span class="risk-badge risk-badge--{{ $level }}">{{ $t['riskLevelLabel'] ?? ucfirst($level) }}</span>
        · <span class="pill pill--{{ $tone }}">{{ $t['statusLabel'] ?? '' }}</span>
      </p>
    </div>
    <a href="/executive" class="btn-outline">Back to dashboard</a>
  </div>

  @if ($level === 'critical')
    <div class="critical-banner" role="status">Critical risk — prioritized for executive oversight</div>
  @elseif ($level === 'high')
    <div class="critical-banner critical-banner--high" role="status">High risk — prioritized for executive oversight</div>
  @endif

  <div class="sup-detail-stack">
    <section class="sup-card">
      <h2>Risk details</h2>
      <dl class="detail-dl detail-dl--console">
        <dt>Submitted by</dt><dd>{{ $t['submittedByName'] ?? '—' }} ({{ $t['department'] ?? '—' }})</dd>
        <dt>Location</dt><dd>{{ $t['location'] ?? '—' }}</dd>
        <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
        <dt>Risk level</dt>
        <dd><span class="risk-badge risk-badge--{{ $level }}">{{ $t['riskLevelLabel'] ?? '—' }}</span></dd>
        <dt>Likelihood × Impact</dt>
        <dd>{{ (int) ($t['likelihood'] ?? 0) }} × {{ (int) ($t['impact'] ?? 0) }} ({{ $t['riskScore'] ?? '—' }})</dd>
        <dt>Status</dt><dd><span class="pill pill--{{ $tone }}">{{ $t['statusLabel'] ?? '—' }}</span></dd>
        <dt>Submitted</dt>
        <dd>{{ !empty($t['submittedAt']) ? \Illuminate\Support\Carbon::parse($t['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
      </dl>
      <p class="sup-detail-desc">{{ $t['description'] ?? '—' }}</p>
    </section>

    <section class="sup-card">
      <h2>5W1H report</h2>
      <x-w1h-readonly :w="$w ?? []" />
    </section>

    <section class="sup-card">
      <h2>Evidence</h2>
      @if (count($attachments ?? []) === 0)
        <p class="sup-muted-block">No evidence files attached.</p>
      @else
        <ul class="attachment-list">
          @foreach ($attachments as $a)
            <li><a href="/executive/attachments/{{ urlencode($a['id']) }}" target="_blank" rel="noopener">{{ $a['name'] }}</a></li>
          @endforeach
        </ul>
      @endif
    </section>

    <section class="sup-card">
      <h2>AI classification</h2>
      @if (empty($t['hasAi']))
        <p class="sup-muted-block">No AI classification available.</p>
      @else
        <p class="sup-muted-block">{{ $t['aiSummary'] ?? '—' }}</p>
        <dl class="detail-dl detail-dl--console">
          <dt>Risk level</dt>
          <dd><span class="risk-badge risk-badge--{{ $level }}">{{ $t['riskLevelLabel'] ?? '—' }}</span></dd>
          <dt>Likelihood</dt><dd>{{ $t['aiLikelihood'] ?? $t['likelihood'] ?? '—' }}/5</dd>
          <dt>Impact</dt><dd>{{ $t['aiImpact'] ?? $t['impact'] ?? '—' }}/5</dd>
          <dt>Confidence</dt>
          <dd>{{ isset($t['aiConfidence']) ? round(((float) $t['aiConfidence']) * 100).'%' : '—' }}</dd>
        </dl>
      @endif
    </section>

    @if (!empty($t['officerNotes']))
      <section class="sup-card sup-card--accent">
        <h2>RMO mitigation solution</h2>
        <p>{{ $t['officerNotes'] }}</p>
        @if (!empty($t['mitigationDueAt']))
          <p class="sup-muted-block">Implementation due: {{ \Illuminate\Support\Carbon::parse($t['mitigationDueAt'])->format('Y-m-d') }}</p>
        @endif
      </section>
    @endif

    <section class="sup-card">
      <h2>Discussion thread</h2>
      <p class="sup-muted-block">Executive Committee is <strong>view only</strong>. Comments and workflow changes are not permitted.</p>
      @if (count($tops) === 0)
        <div class="reddit-thread reddit-thread--empty">
          <p class="reddit-empty">No comments yet.</p>
        </div>
      @else
        <div class="reddit-thread">
          @foreach ($tops as $c)
            <div class="reddit-comment" id="comment-{{ $c['id'] }}">
              <div class="reddit-comment__main">
                <header class="reddit-comment__header">
                  <span class="reddit-author">{{ $c['authorName'] }}</span>
                  @if (($c['roleLabel'] ?? '') !== '')
                    <span class="reddit-role">{{ $c['roleLabel'] }}</span>
                  @endif
                  @if (!empty($c['at']))
                    <span class="reddit-sep" aria-hidden="true">·</span>
                    <time class="reddit-time">{{ \Illuminate\Support\Carbon::parse($c['at'])->format('Y-m-d H:i') }}</time>
                  @endif
                </header>
                <div class="reddit-body">{{ $c['body'] }}</div>
                @foreach ($childrenOf($c['id']) as $reply)
                  <div class="reddit-comment reddit-comment--reply" id="comment-{{ $reply['id'] }}">
                    <div class="reddit-comment__main">
                      <header class="reddit-comment__header">
                        <span class="reddit-author">{{ $reply['authorName'] }}</span>
                        @if (($reply['roleLabel'] ?? '') !== '')
                          <span class="reddit-role">{{ $reply['roleLabel'] }}</span>
                        @endif
                        @if (!empty($reply['at']))
                          <span class="reddit-sep" aria-hidden="true">·</span>
                          <time class="reddit-time">{{ \Illuminate\Support\Carbon::parse($reply['at'])->format('Y-m-d H:i') }}</time>
                        @endif
                      </header>
                      <div class="reddit-body">{{ $reply['body'] }}</div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

    <p class="sup-muted-block exec-view-only-hint">View only — approve, reject, transfer, comment, and close actions are not available for this role.</p>
  </div>
@endsection
