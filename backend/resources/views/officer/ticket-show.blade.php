@extends($layoutName ?? 'layouts.officer')

@section('content')
  @php
    $t = $ticket ?? [];
    $w = $fiveW1H ?? [];
    $caps = $capabilities ?? [];
    $plan = $actionPlan ?? null;
    $acc = $accomplishment ?? null;
    $clos = $closure ?? null;
    $ref = $t['reference'] ?? '';
    $prefix = $consolePrefix ?? '/officer';
    $ticketBase = $prefix.'/tickets/'.rawurlencode($ref);
    $attachBase = $prefix.'/attachments/';
    $comments = $threadComments ?? [];
    $tops = array_values(array_filter($comments, fn ($c) => empty($c['parentId'])));
    $childrenOf = function (string $parentId) use ($comments) {
      return array_values(array_filter($comments, fn ($c) => ($c['parentId'] ?? null) === $parentId));
    };
    $flashLabels = [
      'rmu_thread_comment' => 'Governance comment posted.',
      'ticket_reopened' => 'Ticket reopened and assigned to the selected department.',
      'ai_route_approved' => 'AI routing approved and department ownership opened.',
      'review_recorded' => 'Review decision recorded.',
      'accomplishment_validated' => 'Accomplishment validated and sent to the President.',
      'accomplishment_returned' => 'Accomplishment returned for additional mitigation work.',
    ];
    $flashKey = is_string($flash ?? null) ? $flash : '';
    $flashMsg = $flashLabels[$flashKey] ?? null;
    $errorMsg = is_string($error ?? null) && $error !== '' ? urldecode($error) : null;
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
        <span class="pill pill--{{ !empty($t['isOverdue']) ? 'bad' : 'info' }}">{{ $t['statusLabel'] ?? '' }}</span>
      </p>
    </div>
    <a href="{{ $prefix === '/compliance' ? '/compliance' : '/officer/tickets' }}" class="btn-outline">Back</a>
  </div>

  <div class="sup-detail-stack">
    <section class="sup-card">
      <h2>Ownership (read-only)</h2>
      <p class="sup-muted-block">The RMO monitors this ticket but does <strong>not</strong> own it. Ownership rests with the responsible department.</p>
      <dl class="detail-dl detail-dl--console">
        <dt>Ownership</dt>
        <dd><span class="pill pill--{{ $t['ownershipTone'] ?? 'muted' }}">{{ $t['ownershipLabel'] ?? 'Unassigned' }}</span></dd>
        <dt>Responsible department</dt><dd>{{ $t['department'] ?? '—' }}</dd>
        <dt>Department owner</dt><dd>{{ !empty($t['ownerName']) ? $t['ownerName'] : '—' }}</dd>
      </dl>
    </section>

    <section class="sup-card">
      <h2>Risk details</h2>
      <dl class="detail-dl detail-dl--console">
        <dt>Submitted by</dt>
        <dd>{{ $t['submittedByName'] ?? '—' }} ({{ $t['reporterDepartment'] ?? '—' }})</dd>
        <dt>Location</dt><dd>{{ $t['location'] ?? '—' }}</dd>
        <dt>Risk level</dt><dd>{{ $t['riskLevelLabel'] ?? '—' }}</dd>
        <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
        <dt>Likelihood × Impact</dt>
        <dd>{{ (int) ($t['likelihood'] ?? 0) }} × {{ (int) ($t['impact'] ?? 0) }} ({{ $t['riskScore'] ?? '—' }})</dd>
        <dt>Submitted</dt>
        <dd>{{ !empty($t['submittedAt']) ? \Illuminate\Support\Carbon::parse($t['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
        @if (!empty($t['dueAt']))
          <dt>SLA / target date</dt>
          <dd>
            {{ \Illuminate\Support\Carbon::parse($t['dueAt'])->format('Y-m-d') }}
            @if (!empty($t['isOverdue']))
              <span class="pill pill--bad">Overdue</span>
            @endif
          </dd>
        @endif
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
            <li>
              <a href="{{ $attachBase }}{{ urlencode($a['id']) }}" target="_blank" rel="noopener">{{ $a['name'] }}</a>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    <section class="sup-card">
      <h2>AI analysis (read-only)</h2>
      @if (empty($t['hasAi']))
        <p class="sup-muted-block">No AI classification available.</p>
      @else
        <p class="sup-muted-block">{{ $t['aiSummary'] ?? '—' }}</p>
        <dl class="detail-dl detail-dl--console">
          <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
          <dt>Likelihood</dt><dd>{{ $t['aiLikelihood'] ?? $t['likelihood'] ?? '—' }}/5</dd>
          <dt>Impact</dt><dd>{{ $t['aiImpact'] ?? $t['impact'] ?? '—' }}/5</dd>
          <dt>Confidence</dt>
          <dd>{{ isset($t['aiConfidence']) ? round(((float) $t['aiConfidence']) * 100).'%' : '—' }}</dd>
          <dt>Manual review</dt><dd>{{ !empty($t['aiManualReview']) ? 'Required' : 'No' }}</dd>
          <dt>Routed department</dt><dd>{{ $t['department'] ?? '—' }}</dd>
        </dl>
      @endif
    </section>

    <section class="sup-card">
      <h2>Department action plan</h2>
      @if (!$plan)
        <p class="sup-muted-block">No department action plan submitted yet.</p>
      @else
        <p>{{ $plan['summary'] }}</p>
        @if (!empty($plan['steps']))
          <ol class="dept-plan__steps">
            @foreach ($plan['steps'] as $step)
              <li>{{ $step }}</li>
            @endforeach
          </ol>
        @endif
        <p class="sup-muted-block">
          v{{ (int) ($plan['version'] ?? 1) }}
          @if (!empty($plan['updatedAt']))
            · updated {{ \Illuminate\Support\Carbon::parse($plan['updatedAt'])->format('Y-m-d H:i') }}
          @endif
          @if (!empty($plan['targetDate']))
            · target {{ $plan['targetDate'] }}
          @endif
        </p>
      @endif
    </section>

    @if ($acc)
      <section class="sup-card">
        <h2>Accomplishment report</h2>
        <p class="sup-muted-block">
          Submitted by {{ $acc['submittedByName'] ?? '—' }}
          @if (!empty($acc['submittedAt']))
            on {{ \Illuminate\Support\Carbon::parse($acc['submittedAt'])->format('Y-m-d H:i') }}
          @endif
        </p>
        <p class="accomplishment-notice">
          @if (($t['status'] ?? '') === 'closed')
            Accomplishment on record. Only the Risk Management Officer can reopen this ticket and reassign it to a department.
          @else
            Accomplishment on record — RMO monitors only; department head closes the ticket.
          @endif
        </p>
        <div class="accomplishment-blocks">
          <div class="accomplishment-block">
            <h3 class="accomplishment-block__label">Implementation summary</h3>
            <p class="accomplishment-block__content">{{ $acc['summary'] }}</p>
          </div>
          <div class="accomplishment-block">
            <h3 class="accomplishment-block__label">Outcomes and results</h3>
            <p class="accomplishment-block__content">{{ $acc['outcomes'] }}</p>
          </div>
          @if (!empty($acc['evidence']))
            <div class="accomplishment-block">
              <h3 class="accomplishment-block__label">Evidence references</h3>
              <ul class="accomplishment-block__list">
                @foreach ($acc['evidence'] as $e)
                  <li>{{ $e['name'] ?? '—' }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </section>
    @endif

    @if ($clos)
      <section class="sup-card">
        <h2>Closure</h2>
        <p>{{ $clos['notes'] ?? 'Ticket closed.' }}</p>
        <p class="sup-muted-block">
          {{ $clos['closedByName'] ?? 'Department' }}
          @if (!empty($clos['closedAt']))
            · {{ \Illuminate\Support\Carbon::parse($clos['closedAt'])->format('Y-m-d H:i') }}
          @endif
        </p>
      </section>
    @endif

    @if (!empty($caps['canApproveAiRoute']))
      <section class="sup-card sup-card--accent">
        <div class="sup-card__head"><h2>Approve AI routing</h2></div>
        <div class="sup-card__body">
          <p class="sup-muted-block">Validate or override the AI department recommendation before ownership opens.</p>
          <form method="post" action="{{ $ticketBase }}/ai-route/approve" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="aiDepartment">Assign to department</label>
              <select id="aiDepartment" name="department" required>
                @foreach ($departments ?? [] as $dept)
                  <option value="{{ $dept }}" @selected(($t['department'] ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Approve routing</button>
          </form>
        </div>
      </section>
    @endif

    @if (!empty($caps['canValidateAccomplishment']))
      <section class="sup-card sup-card--accent">
        <div class="sup-card__head"><h2>Validate accomplishment</h2></div>
        <div class="sup-card__body">
          <p class="sup-muted-block">High/Critical accomplishments require RMU/Compliance validation before presidential final decision.</p>
          <form method="post" action="{{ $ticketBase }}/accomplishment/validate" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="validateNote">Validation note</label>
              <textarea id="validateNote" name="note" rows="3" placeholder="Optional validation comments…"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Validate and send to President</button>
          </form>
          <form method="post" action="{{ $ticketBase }}/accomplishment/return" class="stack-form stack-form--console" style="margin-top:1rem">
            @csrf
            <div class="field field--console">
              <label for="returnNote">Return for revision <span class="text-muted">(required)</span></label>
              <textarea id="returnNote" name="note" rows="3" required placeholder="Explain what must be corrected…"></textarea>
            </div>
            <button type="submit" class="btn-outline btn-primary--auto">Return to mitigation</button>
          </form>
        </div>
      </section>
    @endif

    @if (!empty($caps['canReviewDecision']) && ($prefix ?? '/officer') === '/officer')
      <section class="sup-card">
        <div class="sup-card__head"><h2>RMU review decision</h2></div>
        <div class="sup-card__body">
          <p class="sup-muted-block">Record a formal recommendation or escalate to the President. RMO cannot close tickets.</p>
          <form method="post" action="{{ $ticketBase }}/review-decision" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="reviewDecision">Decision</label>
              <select id="reviewDecision" name="decision" required>
                <option value="recommend">Recommend</option>
                <option value="escalate">Escalate to President</option>
              </select>
            </div>
            <div class="field field--console">
              <label for="reviewNote">Note <span class="text-muted">(required)</span></label>
              <textarea id="reviewNote" name="note" rows="3" required placeholder="Document the recommendation or escalation reason…"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Record decision</button>
          </form>
        </div>
      </section>
    @endif

    @if (!empty($caps['canReopen']))
      <section class="sup-card sup-card--accent officer-reopen-card">
        <div class="sup-card__head"><h2>Reopen ticket</h2></div>
        <div class="sup-card__body">
          <p class="sup-muted-block">Reopen this closed ticket and assign it back to a department for a new ownership cycle.</p>
          <form method="post" action="{{ $ticketBase }}/reopen" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="reopenReason">Reason <span class="text-muted">(required)</span></label>
              <textarea id="reopenReason" name="reason" rows="3" required placeholder="Explain why this ticket must be reopened…"></textarea>
            </div>
            <div class="field field--console">
              <label for="reopenDepartment">Assign to department <span class="text-muted">(required)</span></label>
              <select id="reopenDepartment" name="department" required>
                @foreach ($departments ?? [] as $dept)
                  <option value="{{ $dept }}" @selected(($t['department'] ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Reopen and assign</button>
          </form>
        </div>
      </section>
    @endif

    <section class="sup-card">
      <h2>Discussion thread</h2>
      @if (count($tops) === 0)
        <div class="reddit-thread reddit-thread--empty">
          <p class="reddit-empty">No comments yet. Start the discussion below.</p>
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
                @if (!empty($caps['canPostComment']))
                  <details class="reddit-reply-box">
                    <summary class="reddit-action-btn">Reply</summary>
                    <form method="post" action="{{ $ticketBase }}/thread-comment" class="stack-form reddit-reply-form">
                      @csrf
                      <input type="hidden" name="parentId" value="{{ $c['id'] }}">
                      <div class="field">
                        <label class="visually-hidden" for="reply-{{ $c['id'] }}">Reply</label>
                        <textarea id="reply-{{ $c['id'] }}" name="comment" rows="3" required placeholder="Write a reply…"></textarea>
                      </div>
                      <button type="submit" class="btn-outline btn-primary--auto">Reply</button>
                    </form>
                  </details>
                @endif
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

      @if (!empty($caps['canPostComment']))
        <form method="post" action="{{ $ticketBase }}/thread-comment" class="stack-form reddit-compose">
          @csrf
          <div class="field">
            <label for="thread-comment-{{ $ref }}">Add comment</label>
            <textarea id="thread-comment-{{ $ref }}" name="comment" rows="3" required placeholder="Comment visible to the reporter and responsible department…"></textarea>
          </div>
          <button type="submit" class="btn-primary btn-primary--auto">Post comment</button>
        </form>
      @endif
    </section>
  </div>
@endsection
