@extends('layouts.president')

@section('content')
  @php
    $t = $ticket ?? [];
    $w = $fiveW1H ?? [];
    $caps = $capabilities ?? [];
    $plan = $actionPlan ?? null;
    $ref = $t['reference'] ?? '';
    $level = $t['riskLevel'] ?? 'high';
    $comments = $threadComments ?? [];
    $tops = array_values(array_filter($comments, fn ($c) => empty($c['parentId'])));
    $childrenOf = function (string $parentId) use ($comments) {
      return array_values(array_filter($comments, fn ($c) => ($c['parentId'] ?? null) === $parentId));
    };
    $flashLabels = [
      'president_approve' => 'Action plan approved. Released for implementation.',
      'president_reject' => 'Action plan declined. Department must submit a new plan.',
      'president_return' => 'Action plan returned to the department for revision.',
      'president_close' => 'Ticket closed.',
      'president_comment' => 'Comment posted.',
      'not_found' => 'Ticket not found.',
    ];
    $flashKey = is_string($flash ?? null) ? $flash : '';
    $flashMsg = $flashLabels[$flashKey] ?? null;
    $errorMsg = is_string($error ?? null) && $error !== '' ? urldecode($error) : null;
    $compliance = $compliance ?? ['notes' => '', 'trail' => []];
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
    <a href="/president/pending" class="btn-outline">Back to pending</a>
  </div>

  @if (!empty($caps['canApproveActionPlan']) && $level === 'critical')
    <div class="critical-banner" role="status">Critical risk — action plan requires presidential approval</div>
  @elseif (!empty($caps['canApproveActionPlan']) && $level === 'high')
    <div class="critical-banner critical-banner--high" role="status">High risk — action plan requires presidential approval</div>
  @endif
  @if (($t['status'] ?? '') === 'pending_president_final')
    <div class="critical-banner" role="status">Awaiting your final decision to close or return this ticket</div>
  @endif

  <div class="dept-detail">
    <div class="dept-detail__main">
      <section class="sup-card">
        <h2>Risk details</h2>
        <dl class="detail-dl detail-dl--console">
          <dt>Submitted by</dt><dd>{{ $t['submittedByName'] ?? '—' }} ({{ $t['department'] ?? '—' }})</dd>
          <dt>Location</dt><dd>{{ $t['location'] ?? '—' }}</dd>
          <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
          <dt>Risk level</dt>
          <dd><span class="risk-badge risk-badge--{{ $level }}">{{ $t['riskLevelLabel'] ?? '—' }}</span></dd>
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
              <li><a href="/president/attachments/{{ urlencode($a['id']) }}" target="_blank" rel="noopener">{{ $a['name'] }}</a></li>
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
            <dt>Likelihood</dt><dd>{{ $t['aiLikelihood'] ?? $t['likelihood'] ?? '—' }}/5</dd>
            <dt>Impact</dt><dd>{{ $t['aiImpact'] ?? $t['impact'] ?? '—' }}/5</dd>
          </dl>
        @endif
      </section>

      <section class="sup-card sup-card--accent">
        <h2>Department action plan</h2>
        @if (!$plan)
          <p class="sup-muted-block">No department action plan submitted yet.</p>
        @else
          <p class="dept-plan__summary">{{ $plan['summary'] }}</p>
          @if (!empty($plan['steps']))
            <ol class="dept-plan__steps">
              @foreach ($plan['steps'] as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>
          @endif
          @if (!empty($plan['targetDate']))
            <p class="sup-muted-block">Target: {{ \Illuminate\Support\Carbon::parse($plan['targetDate'])->format('Y-m-d') }}</p>
          @endif
          @if (!empty($plan['updatedByName']))
            <p class="sup-muted-block">Updated by {{ $plan['updatedByName'] }}@if(!empty($plan['updatedAt'])) · {{ \Illuminate\Support\Carbon::parse($plan['updatedAt'])->format('Y-m-d H:i') }}@endif</p>
          @endif
        @endif
      </section>

      @if (!empty($finalResolution))
        <section class="sup-card sup-card--accent">
          <h2>Department resolution</h2>
          <h4>Resolution summary</h4>
          <p>{{ $finalResolution['summary'] }}</p>
          <h4>Outcomes</h4>
          <p>{{ $finalResolution['outcomes'] }}</p>
          <p class="sup-muted-block">Submitted by {{ $finalResolution['submittedByName'] }}@if(!empty($finalResolution['submittedAt'])) on {{ \Illuminate\Support\Carbon::parse($finalResolution['submittedAt'])->format('Y-m-d H:i') }}@endif</p>
        </section>
      @endif

      @if (!empty($rmuRecommendations))
        <section class="sup-card">
          <h2>RMO recommendations</h2>
          <ul class="audit-trail-list">
            @foreach (array_reverse($rmuRecommendations) as $r)
              <li class="audit-trail-item">
                <div class="audit-trail-meta">
                  <span class="audit-trail-action">Recommendation</span>
                  <span class="audit-trail-user">{{ $r['authorName'] }}</span>
                  @if (!empty($r['at']))
                    <span class="audit-trail-time">{{ \Illuminate\Support\Carbon::parse($r['at'])->format('Y-m-d H:i') }}</span>
                  @endif
                </div>
                <p class="audit-trail-current__plan">{{ $r['body'] }}</p>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      @if (($compliance['notes'] ?? '') !== '' || !empty($compliance['trail']))
        <section class="sup-card">
          <h2>Compliance findings</h2>
          @if (($compliance['notes'] ?? '') !== '')
            <p>{{ $compliance['notes'] }}</p>
          @endif
          @if (!empty($compliance['trail']))
            <ul class="audit-trail-list">
              @foreach ($compliance['trail'] as $e)
                <li class="audit-trail-item">
                  <div class="audit-trail-meta">
                    <span class="audit-trail-action">{{ $e['action'] }}</span>
                    @if (!empty($e['at']))
                      <span class="audit-trail-time">{{ \Illuminate\Support\Carbon::parse($e['at'])->format('Y-m-d H:i') }}</span>
                    @endif
                  </div>
                  @if (!empty($e['detail']))
                    <p class="audit-trail-current__plan">{{ $e['detail'] }}</p>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </section>
      @endif

      @foreach ($decisions ?? [] as $d)
        <section class="sup-card sup-card--accent">
          <h2>{{ $d['title'] }}</h2>
          <p><strong>{{ $d['decision'] }}</strong></p>
          @if (!empty($d['note']))
            <p>{{ $d['note'] }}</p>
          @endif
          <p class="sup-muted-block">{{ $d['authorName'] }}@if(!empty($d['at'])) · {{ \Illuminate\Support\Carbon::parse($d['at'])->format('Y-m-d H:i') }}@endif</p>
        </section>
      @endforeach

      <section class="sup-card">
        <h2>Discussion thread</h2>
        @if (count($tops) === 0)
          <p class="sup-muted-block">No comments yet.</p>
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
                </div>
              </div>
            @endforeach
          </div>
        @endif

        @if (!empty($caps['canPostComment']))
          <form method="post" action="/president/tickets/{{ urlencode($ref) }}/comment" class="stack-form reddit-compose">
            @csrf
            <div class="field field--console">
              <label for="pres-comment-{{ $ref }}">Add comment</label>
              <textarea id="pres-comment-{{ $ref }}" name="comment" rows="3" required placeholder="Comment on this High/Critical risk action plan…"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Post comment</button>
          </form>
        @endif
      </section>
    </div>

    <aside class="dept-detail__side">
      <div class="dept-side-card">
        <h3 class="dept-side-card__title">Details</h3>
        <dl class="detail-dl detail-dl--console">
          <dt>Department</dt><dd>{{ $t['department'] ?? '—' }}</dd>
          <dt>Submitted by</dt><dd>{{ $t['submittedByName'] ?? '—' }}</dd>
          <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
          <dt>Risk level</dt>
          <dd><span class="risk-badge risk-badge--{{ $level }}">{{ $t['riskLevelLabel'] ?? '—' }}</span></dd>
          <dt>Status</dt><dd><span class="pill pill--{{ $tone }}">{{ $t['statusLabel'] ?? '—' }}</span></dd>
          <dt>Submitted</dt>
          <dd>{{ !empty($t['submittedAt']) ? \Illuminate\Support\Carbon::parse($t['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
          @if (!empty($t['dueAt']))
            <dt>Target date</dt>
            <dd>{{ \Illuminate\Support\Carbon::parse($t['dueAt'])->format('Y-m-d') }}</dd>
          @endif
        </dl>
      </div>

      @if (!empty($caps['canApproveActionPlan']))
        <section class="dept-action-bar dept-action-bar--side" id="president-decision" aria-label="Action plan decision">
          <div class="dept-action-bar__copy">
            <strong>Action plan decision</strong>
            <p>Approve this High/Critical action plan, or return it for revision before implementation.</p>
          </div>
          <div class="dept-action-bar__buttons">
            <button type="button" class="dept-action-btn dept-action-btn--accept" data-pres-modal-open="approve">Approve action plan</button>
            <button type="button" class="dept-action-btn dept-action-btn--reassign" data-pres-modal-open="return">Return for revision</button>
          </div>
        </section>
      @endif

      @if (!empty($caps['canFinalDecision']))
        <section class="dept-action-bar dept-action-bar--side" aria-label="Final decision">
          <div class="dept-action-bar__copy">
            <strong>Final decision</strong>
            <p>Close the ticket or return it to the department.</p>
          </div>
          <div class="dept-action-bar__buttons">
            <button type="button" class="dept-action-btn dept-action-btn--accept" data-pres-modal-open="close">Close ticket</button>
            <button type="button" class="dept-action-btn dept-action-btn--reassign" data-pres-modal-open="return-final">Return to department</button>
          </div>
        </section>
      @endif
    </aside>
  </div>

  @if (!empty($caps['showModals']))
    @if (!empty($caps['canApproveActionPlan']))
      <div class="dept-modal" id="pres-modal-approve" hidden aria-hidden="true">
        <div class="dept-modal__backdrop" data-pres-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="dept-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pres-modal-approve-title">
          <div class="dept-modal__head">
            <h2 class="dept-modal__title" id="pres-modal-approve-title">Approve action plan</h2>
            <button type="button" class="dept-modal__close" data-pres-modal-close aria-label="Close">&times;</button>
          </div>
          <p class="dept-modal__desc">Release this plan to the reporter for implementation.</p>
          <form method="post" action="/president/tickets/{{ urlencode($ref) }}/decision" class="stack-form stack-form--console dept-modal__form">
            @csrf
            <input type="hidden" name="decision" value="approve">
            <div class="dept-modal__actions">
              <button type="button" class="btn-outline btn-primary--auto" data-pres-modal-close>Cancel</button>
              <button type="submit" class="btn-accept--outline">Approve action plan</button>
            </div>
          </form>
        </div>
      </div>

      <div class="dept-modal" id="pres-modal-return" hidden aria-hidden="true">
        <div class="dept-modal__backdrop" data-pres-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="dept-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pres-modal-return-title">
          <div class="dept-modal__head">
            <h2 class="dept-modal__title" id="pres-modal-return-title">Return for revision</h2>
            <button type="button" class="dept-modal__close" data-pres-modal-close aria-label="Close">&times;</button>
          </div>
          <p class="dept-modal__desc">Send the plan back with revision instructions.</p>
          <form method="post" action="/president/tickets/{{ urlencode($ref) }}/decision" class="stack-form stack-form--console dept-modal__form">
            @csrf
            <input type="hidden" name="decision" value="return">
            <div class="field field--console">
              <label for="returnNote">Instructions <span class="text-muted">(required)</span></label>
              <textarea id="returnNote" name="note" rows="3" required placeholder="What should the department revise…"></textarea>
            </div>
            <div class="dept-modal__actions">
              <button type="button" class="btn-outline btn-primary--auto" data-pres-modal-close>Cancel</button>
              <button type="submit" class="btn-primary btn-primary--auto">Return to department</button>
            </div>
          </form>
        </div>
      </div>
    @endif

    @if (!empty($caps['canFinalDecision']))
      <div class="dept-modal" id="pres-modal-close" hidden aria-hidden="true">
        <div class="dept-modal__backdrop" data-pres-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="dept-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pres-modal-close-title">
          <div class="dept-modal__head">
            <h2 class="dept-modal__title" id="pres-modal-close-title">Close ticket</h2>
            <button type="button" class="dept-modal__close" data-pres-modal-close aria-label="Close">&times;</button>
          </div>
          <p class="dept-modal__desc">Close this ticket after accomplishment review.</p>
          <form method="post" action="/president/tickets/{{ urlencode($ref) }}/decision" class="stack-form stack-form--console dept-modal__form">
            @csrf
            <input type="hidden" name="decision" value="close">
            <div class="field field--console">
              <label for="closeNote">Note <span class="text-muted">(optional)</span></label>
              <textarea id="closeNote" name="note" rows="3" placeholder="Optional closing note…"></textarea>
            </div>
            <div class="dept-modal__actions">
              <button type="button" class="btn-outline btn-primary--auto" data-pres-modal-close>Cancel</button>
              <button type="submit" class="btn-accept--outline">Close ticket</button>
            </div>
          </form>
        </div>
      </div>

      <div class="dept-modal" id="pres-modal-return-final" hidden aria-hidden="true">
        <div class="dept-modal__backdrop" data-pres-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="dept-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pres-modal-return-final-title">
          <div class="dept-modal__head">
            <h2 class="dept-modal__title" id="pres-modal-return-final-title">Return to department</h2>
            <button type="button" class="dept-modal__close" data-pres-modal-close aria-label="Close">&times;</button>
          </div>
          <p class="dept-modal__desc">Return the ticket for further work.</p>
          <form method="post" action="/president/tickets/{{ urlencode($ref) }}/decision" class="stack-form stack-form--console dept-modal__form">
            @csrf
            <input type="hidden" name="decision" value="return">
            <div class="field field--console">
              <label for="returnNoteFinal">Reason <span class="text-muted">(required)</span></label>
              <textarea id="returnNoteFinal" name="note" rows="3" required placeholder="What should the department revise or complete…"></textarea>
            </div>
            <div class="dept-modal__actions">
              <button type="button" class="btn-outline btn-primary--auto" data-pres-modal-close>Cancel</button>
              <button type="submit" class="btn-primary btn-primary--auto">Return ticket</button>
            </div>
          </form>
        </div>
      </div>
    @endif

    <script>
    (function () {
      function closeAllPresModals() {
        document.querySelectorAll('.dept-modal:not([hidden])').forEach(function (modal) {
          modal.hidden = true;
          modal.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('dept-modal-open');
      }

      function openPresModal(id) {
        closeAllPresModals();
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('dept-modal-open');
        var focusable = modal.querySelector('textarea, select, button:not(.dept-modal__close)');
        if (focusable) focusable.focus();
      }

      document.querySelectorAll('[data-pres-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          openPresModal('pres-modal-' + btn.getAttribute('data-pres-modal-open'));
        });
      });

      document.querySelectorAll('[data-pres-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeAllPresModals);
      });

      document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') closeAllPresModals();
      });
    })();
    </script>
  @endif
@endsection
