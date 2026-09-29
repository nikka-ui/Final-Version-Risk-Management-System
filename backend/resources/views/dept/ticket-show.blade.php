@extends('layouts.dept')

@section('content')
  @php
    $t = $ticket ?? [];
    $w = $fiveW1H ?? [];
    $caps = $capabilities ?? [];
    $plan = $actionPlan ?? null;
    $ref = $t['reference'] ?? '';
    $flashLabels = [
      'ownership_accepted' => 'Ownership accepted.',
      'ownership_rejected' => 'Ownership rejected.',
      'ticket_reassigned' => 'Ticket transferred.',
      'action_plan_saved' => 'Action plan draft saved.',
      'action_plan_submitted' => 'Action plan submitted.',
      'action_plan_published' => 'Action plan sent to reporter.',
      'report_returned' => 'Ticket returned to reporter.',
      'ticket_closed_dept' => 'Ticket closed.',
      'resolution_submitted' => 'Resolution submitted.',
      'documents_uploaded_dept' => 'Documents uploaded.',
      'personnel_assigned' => 'Personnel updated.',
      'dept_comment_posted' => 'Comment posted.',
    ];
    $flashKey = is_string($flash ?? null) ? $flash : '';
    $flashMsg = $flashLabels[$flashKey] ?? null;
    $errorMsg = is_string($error ?? null) && $error !== '' ? urldecode($error) : null;
    $backHref = ($t['status'] ?? '') === 'assigned' ? '/dept/inbox' : '/dept/tickets';
    $backLabel = ($t['status'] ?? '') === 'assigned' ? 'Back to inbox' : 'Back to tickets';
    $submitLabel = !empty($t['needsPresident'])
      ? 'Submit to President for approval'
      : 'Send to reporter for implementation';
  @endphp

  @if ($flashMsg)
    <div class="alert alert--ok" role="status">{{ $flashMsg }}</div>
  @endif
  @if ($errorMsg)
    <div class="alert alert--error" role="alert">{{ $errorMsg }}</div>
  @endif

  @if (!empty($t['returnedByPresident']) && (($t['presidentPlanNote'] ?? '') !== '' || ($t['presidentFinalNote'] ?? '') !== ''))
    <div class="dept-status-notice dept-status-notice--error" role="note">
      <strong>Returned by President.</strong>
      {{ $t['presidentPlanNote'] ?: $t['presidentFinalNote'] }}
    </div>
  @endif

  @if (($t['status'] ?? '') === 'ownership_rejected')
    <div class="dept-status-notice dept-status-notice--error" role="note">
      This ticket was returned to the reporter{{ !empty($t['rejectionReason']) ? ': '.$t['rejectionReason'] : '' }}. Awaiting reporter revision.
    </div>
  @elseif (($t['status'] ?? '') === 'in_mitigation')
    <div class="dept-status-notice dept-status-notice--info" role="note">
      Mitigation plan sent to the reporter. Awaiting their implementation and accomplishment report.
    </div>
  @elseif (($t['status'] ?? '') === 'pending_audit')
    <div class="dept-status-notice dept-status-notice--info" role="note">
      The reporter submitted an accomplishment report. Review it below, then use <strong>Close ticket</strong> to validate and close.
    </div>
  @elseif (($t['status'] ?? '') === 'closed')
    <div class="dept-status-notice dept-status-notice--success" role="note">
      This ticket is closed{{ !empty($t['closureByName']) ? ' by '.$t['closureByName'] : '' }}{{ !empty($t['closureAt']) ? ' on '.\Illuminate\Support\Carbon::parse($t['closureAt'])->format('Y-m-d H:i') : '' }}.
    </div>
  @endif

  <div class="sup-page-head">
    <div>
      <h1>{{ $t['title'] ?? 'Ticket' }}</h1>
      <p class="sup-page-desc mono">{{ $ref }} · {{ $t['statusLabel'] ?? '' }}</p>
    </div>
    <a href="{{ $backHref }}" class="btn-outline">{{ $backLabel }}</a>
  </div>

  <div class="dept-detail">
    <div class="dept-detail__main">
      <section class="sup-card">
        <h2>Risk report</h2>
        <dl class="detail-dl detail-dl--console">
          <dt>Location</dt><dd>{{ $t['location'] ?? '—' }}</dd>
          <dt>Reported by</dt>
          <dd>{{ $t['submittedByName'] ?? '—' }}{{ !empty($t['reporterDepartment']) ? ' ('.$t['reporterDepartment'].')' : '' }}</dd>
        </dl>
        <p class="sup-detail-desc">{{ $t['description'] ?? '—' }}</p>
      </section>

      <section class="sup-card">
        <h2>5W1H report</h2>
        <div class="w1h-grid w1h-grid--readonly">
          @foreach ([
            'what' => 'What happened?',
            'why' => 'Why did it happen?',
            'where' => 'Where did it occur?',
            'when' => 'When did it occur?',
            'who' => 'Who was involved?',
            'how' => 'How was it discovered?',
          ] as $key => $label)
            <div class="w1h-item">
              <span class="w1h-label">{{ $label }}</span>
              <p>{{ ($w[$key] ?? '') !== '' ? $w[$key] : '—' }}</p>
            </div>
          @endforeach
        </div>
      </section>

      <section class="sup-card">
        <h2>Evidence</h2>
        @if (count($attachments ?? []) === 0)
          <p class="text-muted">No attachments on this ticket.</p>
        @else
          <ul class="evidence-list">
            @foreach ($attachments as $file)
              <li>
                <a href="/dept/attachments/{{ urlencode($file['id']) }}" target="_blank" rel="noopener">{{ $file['name'] }}</a>
                @if (!empty($file['size']))
                  <span class="text-muted">· {{ number_format($file['size'] / 1024 / 1024, 2) }} MB</span>
                @endif
              </li>
            @endforeach
          </ul>
        @endif
      </section>

      @if (!empty($t['aiSummary']) || !empty($t['suggestedMitigation']))
        <section class="sup-card">
          <h2>AI classification &amp; routing</h2>
          @if (!empty($t['aiSummary']))
            <p class="sup-muted-block">{{ $t['aiSummary'] }}</p>
          @endif
          <dl class="detail-dl detail-dl--console">
            @if (!empty($t['aiLikelihood']) || !empty($t['likelihood']))
              <dt>Likelihood</dt><dd>{{ $t['aiLikelihood'] ?? $t['likelihood'] }}/5</dd>
            @endif
            @if (!empty($t['aiImpact']) || !empty($t['impact']))
              <dt>Impact</dt><dd>{{ $t['aiImpact'] ?? $t['impact'] }}/5</dd>
            @endif
            @if (isset($t['aiConfidence']) && $t['aiConfidence'] !== null)
              <dt>Confidence</dt><dd>{{ round(((float) $t['aiConfidence']) * 100) }}%</dd>
            @endif
          </dl>
          @if (!empty($t['suggestedMitigation']))
            <div class="dept-suggested">
              <strong>Suggested initial mitigation</strong>
              <p>{{ $t['suggestedMitigation'] }}</p>
            </div>
          @endif
        </section>
      @endif

      @if (count($reassignments ?? []) > 0)
        <section class="sup-card">
          <h2>Reassignment history</h2>
          <ul class="audit-trail-list">
            @foreach ($reassignments as $r)
              <li class="audit-trail-item">
                <div class="audit-trail-meta">
                  <span class="audit-trail-action">{{ $r['fromDepartment'] }} → {{ $r['toDepartment'] }}</span>
                  <span class="audit-trail-user">{{ $r['byName'] }}</span>
                  <span class="audit-trail-time">{{ !empty($r['at']) ? \Illuminate\Support\Carbon::parse($r['at'])->format('Y-m-d H:i') : '' }}</span>
                </div>
                <p class="audit-trail-current__plan">{{ $r['reason'] }}</p>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      <section class="sup-card sup-card--accent">
        <div class="sup-card__head">
          <h2>
            Action plan
            @if ($plan)
              <span class="text-muted">(v{{ $plan['version'] }})</span>
              @if (!empty($plan['isDraft']))
                <span class="pill pill--warn">Draft</span>
              @endif
            @endif
          </h2>
        </div>
        @if ($plan)
          <div class="sup-card__body">
            <p class="dept-plan__summary">{{ $plan['summary'] }}</p>
            @if (count($plan['steps'] ?? []) > 0)
              <ol class="dept-plan__steps">
                @foreach ($plan['steps'] as $step)
                  <li>{{ $step }}</li>
                @endforeach
              </ol>
            @endif
            <p class="sup-muted-block">
              Updated {{ !empty($plan['updatedAt']) ? \Illuminate\Support\Carbon::parse($plan['updatedAt'])->format('Y-m-d H:i') : '—' }}
              by {{ $plan['updatedByName'] ?? '—' }}
              @if (!empty($plan['targetDate'])) · target {{ $plan['targetDate'] }} @endif
              @if (!empty($plan['publishedAt'])) · sent {{ \Illuminate\Support\Carbon::parse($plan['publishedAt'])->format('Y-m-d H:i') }} @endif
            </p>
          </div>
        @else
          <div class="sup-card__body"><p class="sup-muted-block">No action plan yet.</p></div>
        @endif

        @if (!empty($caps['canUploadDocuments']))
          <div class="sup-card__body">
            <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/documents" class="stack-form stack-form--console" enctype="multipart/form-data">
              @csrf
              <div class="field field--console">
                <label for="deptDocs">Supporting documents</label>
                <p class="section-hint text-muted">Upload PDF, PNG, or JPG (max 20MB each) once the ticket is in progress.</p>
                <input id="deptDocs" name="attachments" type="file" multiple accept=".pdf,.png,.jpg,.jpeg" required>
              </div>
              <button type="submit" class="btn-primary btn-primary--auto">Upload documents</button>
            </form>
          </div>
        @endif

        @if (!empty($caps['canEditActionPlan']))
          <div class="sup-card__body">
            <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/action-plan" class="stack-form stack-form--console dept-inline-form">
              @csrf
              <div class="field field--console">
                <label for="planSummary">{{ $plan ? 'Update action plan' : 'Action plan summary' }}</label>
                <textarea id="planSummary" name="summary" rows="3" required placeholder="Describe the corrective actions the department will take…">{{ $plan['summary'] ?? '' }}</textarea>
              </div>
              <div class="field field--console">
                <label for="planSteps">Action steps <span class="text-muted">(one per line, optional)</span></label>
                <textarea id="planSteps" name="steps" rows="3" placeholder="Step 1&#10;Step 2">{{ implode("\n", $plan['steps'] ?? []) }}</textarea>
              </div>
              <div class="field field--console">
                <label for="planTarget">Target completion date <span class="text-muted">(required to submit)</span></label>
                <input id="planTarget" name="targetDate" type="date" value="{{ $plan['targetDate'] ?? '' }}">
              </div>
              <button type="submit" class="btn-accept--outline">{{ $plan ? 'Save draft' : 'Save action plan draft' }}</button>
              <button type="submit" name="submitForReview" value="1" class="btn-primary btn-primary--auto">{{ $submitLabel }}</button>
            </form>
          </div>
        @endif
      </section>

      @if (count($personnel ?? []) > 0 || !empty($caps['canAssignPersonnel']))
        <section class="sup-card">
          <h2>Assigned personnel</h2>
          @if (count($personnel ?? []) === 0)
            <p class="sup-muted-block">No personnel assigned yet.</p>
          @else
            <ul class="audit-trail-list">
              @foreach ($personnel as $person)
                <li class="audit-trail-item">
                  <div class="audit-trail-meta">
                    <span class="audit-trail-action">{{ $person['name'] }}</span>
                    @if (!empty($person['role']))
                      <span class="audit-trail-user">{{ $person['role'] }}</span>
                    @endif
                    @if (!empty($person['assignedAt']))
                      <span class="audit-trail-time">{{ \Illuminate\Support\Carbon::parse($person['assignedAt'])->format('Y-m-d H:i') }}</span>
                    @endif
                  </div>
                  @if (!empty($person['assignedByName']))
                    <p class="audit-trail-current__plan">Assigned by {{ $person['assignedByName'] }}</p>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
          @if (!empty($caps['canAssignPersonnel']))
            <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/personnel" class="stack-form stack-form--console" style="margin-top:1rem">
              @csrf
              <div class="field field--console">
                <label for="personName">Name <span class="text-muted">(required)</span></label>
                <input id="personName" name="personName" type="text" required placeholder="Staff member name">
              </div>
              <div class="field field--console">
                <label for="personRole">Role <span class="text-muted">(optional)</span></label>
                <input id="personRole" name="personRole" type="text" placeholder="e.g. Implementer">
              </div>
              <button type="submit" class="btn-primary btn-primary--auto">Assign personnel</button>
            </form>
          @endif
        </section>
      @endif

      @if ($accomplishment)
        <section class="sup-card">
          <h2>Accomplishment report</h2>
          <dl class="detail-dl detail-dl--console">
            <dt>Submitted</dt>
            <dd>{{ !empty($accomplishment['submittedAt']) ? \Illuminate\Support\Carbon::parse($accomplishment['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
          </dl>
          <p><strong>Summary</strong></p>
          <p>{{ $accomplishment['summary'] }}</p>
          <p><strong>Outcomes</strong></p>
          <p>{{ $accomplishment['outcomes'] }}</p>
        </section>
      @endif

      @if (count($timeline ?? []) > 0)
        <section class="sup-card">
          <h2>Activity</h2>
          <ul class="audit-trail-list">
            @foreach ($timeline as $event)
              <li class="audit-trail-item">
                <div class="audit-trail-meta">
                  <span class="audit-trail-action">{{ $event['action'] }}</span>
                  <span class="audit-trail-user">{{ $event['actorName'] }}</span>
                  <span class="audit-trail-time">{{ !empty($event['at']) ? \Illuminate\Support\Carbon::parse($event['at'])->format('Y-m-d H:i') : '' }}</span>
                </div>
                @if (($event['detail'] ?? '') !== '')
                  <p class="audit-trail-current__plan">{{ $event['detail'] }}</p>
                @endif
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      @include('partials.thread-discussion', [
        'threadComments' => $threadComments ?? [],
        'user' => $user ?? [],
        'canPost' => !empty($caps['canPostComment']),
        'postAction' => '/dept/tickets/'.urlencode($ref).'/comment',
        'editAction' => '/dept/tickets/'.urlencode($ref).'/comment/edit',
        'reactAction' => '/dept/tickets/'.urlencode($ref).'/comment/react',
        'composeId' => 'dept-comment-'.$ref,
        'composeLabel' => 'Add comment',
        'composePlaceholder' => 'Comment visible to the reporter and Risk Management Officer…',
      ])
    </div>

    <aside class="dept-detail__side">
      @if (!empty($t['reassignedFrom']))
        <div class="dept-transfer-note" role="note">
          <div class="dept-transfer-note__body">
            <strong>Department transfer</strong>
            <p>Transferred from <span class="dept-transfer-note__from">{{ $t['reassignedFrom'] }}</span> to {{ $t['department'] ?? '—' }}</p>
          </div>
        </div>
      @endif

      <div class="dept-side-card">
        <h3 class="dept-side-card__title">Details</h3>
        <dl class="detail-dl detail-dl--console">
          <dt>Ownership</dt>
          <dd><span class="pill pill--{{ $t['ownershipTone'] ?? 'muted' }}">{{ $t['ownershipLabel'] ?? '—' }}</span></dd>
          <dt>Owner</dt><dd>{{ $t['ownerName'] ?: 'Unassigned' }}</dd>
          <dt>Department</dt><dd>{{ $t['department'] ?? '—' }}</dd>
          <dt>Reporter</dt><dd>{{ $t['submittedByName'] ?? '—' }}</dd>
          <dt>Reporter dept.</dt><dd>{{ $t['reporterDepartment'] ?? '—' }}</dd>
          <dt>Category</dt><dd>{{ $t['categoryLabel'] ?? '—' }}</dd>
          <dt>Risk level</dt>
          <dd><span class="risk-badge risk-badge--{{ $t['riskLevel'] ?? 'low' }}">{{ $t['riskLevelLabel'] ?? '—' }}</span></dd>
          <dt>Priority</dt><dd>{{ $t['priority'] ?: '—' }}</dd>
          <dt>Likelihood × Impact</dt>
          <dd>
            @if (!empty($t['likelihood']) && !empty($t['impact']))
              {{ $t['likelihood'] }} × {{ $t['impact'] }}@if (!empty($t['riskScore'])) ({{ $t['riskScore'] }}) @endif
            @else
              —
            @endif
          </dd>
          <dt>Submitted</dt>
          <dd>{{ !empty($t['submittedAt']) ? \Illuminate\Support\Carbon::parse($t['submittedAt'])->format('Y-m-d H:i') : '—' }}</dd>
          @if (!empty($t['dueAt']))
            <dt>Target date</dt>
            <dd class="{{ !empty($t['isOverdue']) ? 'cell--overdue' : '' }}">
              {{ \Illuminate\Support\Carbon::parse($t['dueAt'])->format('Y-m-d') }}
              @if (!empty($t['isOverdue']))
                <span class="pill pill--bad pill--overdue">Overdue</span>
              @endif
            </dd>
          @endif
        </dl>
      </div>

      @if (!empty($caps['canAccept']))
        <section class="sup-card">
          <h3>Ownership decision</h3>
          <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/accept" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="acceptComment">Comment <span class="text-muted">(optional)</span></label>
              <textarea id="acceptComment" name="comment" rows="2" placeholder="Optional note when accepting…"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Accept ownership</button>
          </form>
          <details class="dept-inline-details" style="margin-top:1rem">
            <summary>Reject ownership</summary>
            <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/reject" class="stack-form stack-form--console" style="margin-top:0.75rem">
              @csrf
              <div class="field field--console">
                <label for="rejectReason">Reason <span class="text-muted">(required)</span></label>
                <textarea id="rejectReason" name="reason" rows="3" required placeholder="Explain why this ticket does not belong to your department…"></textarea>
              </div>
              <button type="submit" class="btn-danger--outline">Reject ownership</button>
            </form>
          </details>
        </section>
      @endif

      @if (!empty($caps['canReassign']))
        <section class="sup-card">
          <h3>Transfer ticket</h3>
          <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/reassign" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="reassignReason">Reason <span class="text-muted">(required)</span></label>
              <textarea id="reassignReason" name="reason" rows="2" required></textarea>
            </div>
            <div class="field field--console">
              <label for="reassignComment">Comment <span class="text-muted">(required)</span></label>
              <textarea id="reassignComment" name="comment" rows="2" required></textarea>
            </div>
            <div class="field field--console">
              <label for="reassignTarget">Transfer to</label>
              <select id="reassignTarget" name="targetDepartment" required>
                <option value="">Select department…</option>
                @foreach ($departments ?? [] as $deptName)
                  <option value="{{ $deptName }}">{{ $deptName }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Transfer ticket</button>
          </form>
        </section>
      @endif

      @if (!empty($caps['canReturn']))
        <section class="dept-return-card" aria-label="Return for revision">
          <div class="dept-return-card__copy">
            <strong>Return for revision</strong>
            <p>Send the ticket back to the reporter if the report is incomplete or needs correction.</p>
          </div>
          <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/return" class="stack-form stack-form--console">
            @csrf
            <div class="field field--console">
              <label for="returnReason">Reason for return</label>
              <textarea id="returnReason" name="reason" rows="3" required></textarea>
            </div>
            <button type="submit" class="dept-return-card__btn dept-return-card__btn--inline">Return to reporter</button>
          </form>
        </section>
      @endif

      @if (!empty($caps['canClose']))
        <section class="sup-card">
          <h3>Close ticket</h3>
          <form method="post" action="/dept/tickets/{{ urlencode($ref) }}/close" class="stack-form stack-form--console"
            onsubmit="return confirm('Close {{ $ref }} after reviewing the accomplishment?');">
            @csrf
            <div class="field field--console">
              <label for="closeComment">Closure note <span class="text-muted">(optional)</span></label>
              <textarea id="closeComment" name="closingNotes" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-primary--auto">Close ticket</button>
          </form>
        </section>
      @endif
    </aside>
  </div>
@endsection
