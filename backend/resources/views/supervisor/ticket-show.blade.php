@extends('layouts.supervisor')

@section('content')
  @php
    $t = $ticket;
    $w = $fiveW1H ?? [];
    $flashLabels = [
      'submitted' => 'Ticket submitted successfully.',
      'draft_saved' => 'Draft saved.',
      'evidence_added' => 'Evidence uploaded.',
      'comment_posted' => 'Comment posted.',
      'not_found' => 'Ticket not found.',
    ];
    $flashKey = is_string($flash ?? null) ? $flash : '';
    $flashMsg = $flashLabels[$flashKey] ?? null;
  @endphp
  @if ($flashMsg)
    <div class="alert alert--ok" role="status">{{ $flashMsg }}</div>
  @endif
  @if (!empty($error))
    <div class="alert" role="alert">{{ urldecode((string) $error) }}</div>
  @endif

  <div class="sup-page-head">
    <div>
      <h1 class="mono">{{ $t['reference'] }}</h1>
      <p class="sup-page-desc">{{ $t['title'] }} · {{ $t['statusLabel'] }}</p>
    </div>
    <div class="sup-page-head__actions">
      <a href="/supervisor/tickets" class="btn-outline">Back to list</a>
      <a href="/supervisor/tickets/{{ urlencode($t['reference']) }}/edit" class="btn-outline">Open Express editor</a>
    </div>
  </div>

  <section class="sup-card">
    <h2>Risk details</h2>
    <dl class="detail-dl detail-dl--console">
      <dt>Title</dt><dd>{{ $t['title'] }}</dd>
      <dt>Status</dt><dd>{{ $t['statusLabel'] }}</dd>
      <dt>Reporting unit</dt><dd>{{ $t['reporterDepartment'] }} <span class="text-muted">(not used for AI routing)</span></dd>
      <dt>Responsible department</dt><dd>{{ $t['department'] }}</dd>
      <dt>Location</dt><dd>{{ $t['location'] }}</dd>
      <dt>Category</dt><dd>{{ $t['categoryLabel'] }}</dd>
      <dt>Priority</dt><dd>{{ $t['priority'] ?: '—' }}</dd>
      <dt>Likelihood × Impact</dt>
      <dd>
        @if ($t['likelihood'] && $t['impact'])
          {{ $t['likelihood'] }} × {{ $t['impact'] }}
          @if ($t['riskScore']) ({{ $t['riskScore'] }}) @endif
        @else
          —
        @endif
      </dd>
      @if (!empty($t['dueAt']))
        <dt>Target date</dt>
        <dd>{{ \Illuminate\Support\Carbon::parse($t['dueAt'])->format('Y-m-d') }}</dd>
      @endif
      <dt>Submitted</dt>
      <dd>{{ !empty($t['submittedAt']) ? \Illuminate\Support\Carbon::parse($t['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
      <dt>Updated</dt>
      <dd>{{ !empty($t['updatedAt']) ? \Illuminate\Support\Carbon::parse($t['updatedAt'])->format('Y-m-d H:i') : '—' }}</dd>
    </dl>
    <p style="margin-top:1rem">{{ $t['description'] }}</p>
  </section>

  @if (!empty($t['aiSummary']) || !empty($t['aiConfidence']))
    <section class="sup-card">
      <h2>AI analysis</h2>
      @if (!empty($t['aiConfidence']))
        <p class="text-muted">Confidence: {{ $t['aiConfidence'] }}</p>
      @endif
      @if (!empty($t['aiSummary']))
        <p>{{ $t['aiSummary'] }}</p>
      @endif
    </section>
  @endif

  <section class="sup-card">
    <h2>5W1H</h2>
    <x-w1h-readonly :w="$w ?? []" />
  </section>

  <section class="sup-card">
    <h2>Evidence</h2>
    @if (count($attachments) === 0)
      <p class="text-muted">No attachments on this ticket.</p>
    @else
      <ul class="evidence-list">
        @foreach ($attachments as $file)
          <li>
            <a href="/supervisor/attachments/{{ urlencode($file['id']) }}" target="_blank" rel="noopener">{{ $file['name'] }}</a>
            @if (!empty($file['size']))
              <span class="text-muted">· {{ number_format($file['size'] / 1024 / 1024, 2) }} MB</span>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
    @if (!empty($capabilities['canUploadEvidence']))
      <form method="post" action="/supervisor/tickets/{{ urlencode($t['reference']) }}/evidence" class="stack-form" enctype="multipart/form-data" style="margin-top:1rem">
        @csrf
        <h3>Add evidence</h3>
        <p class="section-hint text-muted">Upload PDF, PNG, or JPG (max 20MB each).</p>
        <input name="attachments" type="file" multiple accept=".pdf,.png,.jpg,.jpeg" required>
        <button type="submit" class="btn-primary btn-primary--auto">Upload files</button>
      </form>
    @endif
  </section>

  @if ($accomplishment)
    <section class="sup-card card--accent">
      <h2>Accomplishment</h2>
      <p class="text-muted">Submitted {{ !empty($accomplishment['submittedAt']) ? \Illuminate\Support\Carbon::parse($accomplishment['submittedAt'])->format('Y-m-d H:i') : '—' }} · sent to your department head for review and closure.</p>
      <dl class="detail-dl detail-dl--console">
        <dt>Summary</dt><dd>{{ $accomplishment['summary'] }}</dd>
        <dt>Outcomes</dt><dd>{{ $accomplishment['outcomes'] }}</dd>
      </dl>
    </section>
  @elseif (!empty($capabilities['canSubmitAccomplishment']))
    <section class="sup-card card--accent">
      <h2>Submit accomplishment report</h2>
      @if (!empty($actionPlanSummary))
        <p class="accomplishment-plan-ref"><strong>Department action plan:</strong> {{ $actionPlanSummary }}</p>
      @endif
      <form method="post" action="/supervisor/tickets/{{ urlencode($t['reference']) }}/accomplishment" class="stack-form accomplishment-report-form" enctype="multipart/form-data">
        @csrf
        <div class="field field--required">
          <label for="accSummary">Implementation summary <span class="req">*</span></label>
          <textarea id="accSummary" name="summary" rows="4" required placeholder="What did you implement from the action plan?"></textarea>
        </div>
        <div class="field field--required">
          <label for="accOutcomes">Outcomes and results <span class="req">*</span></label>
          <textarea id="accOutcomes" name="outcomes" rows="4" required placeholder="What changed as a result?"></textarea>
        </div>
        <div class="field field--required">
          <label for="accAttachments">Accomplishment result evidence <span class="req">*</span></label>
          <p class="field-hint">Required — at least one file proving the department action plan was applied (PDF, PNG, or JPG, max 20MB). Original ticket attachments do not count.</p>
          @if (count($implementationEvidence ?? []) > 0)
            <p class="upload-evidence-status upload-evidence-status--ok">{{ count($implementationEvidence) }} action-plan proof file{{ count($implementationEvidence) === 1 ? '' : 's' }} already attached.</p>
            <ul class="evidence-list">
              @foreach ($implementationEvidence as $file)
                <li><a href="/supervisor/attachments/{{ urlencode($file['id']) }}" target="_blank" rel="noopener">{{ $file['name'] }}</a></li>
              @endforeach
            </ul>
          @endif
          <input id="accAttachments" name="attachments" type="file" multiple accept=".pdf,.png,.jpg,.jpeg" @if (count($implementationEvidence ?? []) === 0) required @endif>
        </div>
        <button type="submit" class="btn-primary btn-primary--auto">Submit accomplishment</button>
      </form>
    </section>
  @endif

  <section class="sup-card dept-collapse">
    <details>
      <summary class="dept-collapse__summary">
        <span class="dept-collapse__row">
          <h2>Ticket timeline</h2>
          <svg class="dept-collapse__icon" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="6 9 12 15 18 9" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </summary>
      <div class="dept-collapse__body">
        @if (count($timeline) === 0)
          <p class="text-muted">Lifecycle events will appear here after workflow updates sync to Laravel.</p>
        @else
          <ul class="ticket-timeline">
            @foreach ($timeline as $event)
              <li class="ticket-timeline-item">
                <div class="ticket-timeline-item__body">
                  <div class="ticket-timeline-item__meta">
                    <strong>{{ $event['action'] }}</strong>
                    @if (!empty($event['at']))
                      <span class="ticket-timeline-item__time">{{ $event['at'] }}</span>
                    @endif
                  </div>
                  @if (!empty($event['detail']))
                    <p class="ticket-timeline-item__detail">{{ $event['detail'] }}</p>
                  @endif
                  @if (!empty($event['actorName']))
                    <span class="ticket-timeline-item__actor">{{ $event['actorName'] }}</span>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </details>
  </section>

  @include('partials.thread-discussion', [
    'threadComments' => $threadComments ?? [],
    'user' => $user ?? [],
    'canPost' => true,
    'postAction' => '/supervisor/tickets/'.urlencode($t['reference']).'/comment',
    'editAction' => '/supervisor/tickets/'.urlencode($t['reference']).'/comment/edit',
    'reactAction' => '/supervisor/tickets/'.urlencode($t['reference']).'/comment/react',
    'composeId' => 'sup-comment-'.$t['reference'],
    'composeLabel' => 'Add comment',
    'composePlaceholder' => 'Write a comment…',
  ])
@endsection
