<?php

namespace App\Http\Controllers;

use App\Services\DeptTicketDetailService;
use App\Services\DeptTicketService;
use App\Services\ExpressOrgMirrorService;
use App\Services\ThreadCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Phase 5 slice 24 + Phase 7 slice 6 + slice 13 + Phase 8 slice 3–5: Department Head ticket detail + workflow/comment/document/personnel + comment edit/react POSTs.
 */
class DeptTicketDetailController extends Controller
{
    public function __construct(
        private readonly DeptTicketDetailService $detail,
        private readonly DeptTicketService $deptTickets,
        private readonly ThreadCommentService $threadComments,
        private readonly ExpressOrgMirrorService $orgMirror,
    ) {}

    public function show(Request $request, string $reference): View|RedirectResponse
    {
        $user = $request->user();
        $payload = $this->detail->forUser($user, $reference);

        if (! $payload) {
            return redirect()->away('/dept/tickets?flash=not_found');
        }

        return view('dept.ticket-show', [
            'user' => $user->toIdentityArray(),
            'activeNav' => $payload['activeNav'],
            'title' => $payload['ticket']['reference'],
            'stats' => $payload['stats'],
            'ticket' => $payload['ticket'],
            'fiveW1H' => $payload['fiveW1H'],
            'attachments' => $payload['attachments'],
            'planDocuments' => $payload['planDocuments'],
            'actionPlan' => $payload['actionPlan'],
            'accomplishment' => $payload['accomplishment'],
            'timeline' => $payload['timeline'],
            'reassignments' => $payload['reassignments'],
            'personnel' => $payload['personnel'],
            'threadComments' => $payload['threadComments'],
            'departments' => $payload['departments'],
            'capabilities' => $payload['capabilities'],
            'flash' => $request->query('flash'),
            'error' => $request->query('error'),
        ]);
    }

    public function accept(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->accept($ticket, $user, $input),
            '/dept/tickets/'.rawurlencode($reference).'?flash=ownership_accepted',
        );
    }

    public function reject(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->reject($ticket, $user, $input),
            '/dept/inbox?flash=ownership_rejected',
        );
    }

    public function returnForRevision(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->returnForRevision($ticket, $user, $input),
            '/dept/tickets?flash=report_returned',
        );
    }

    public function reassign(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->reassign($ticket, $user, $input),
            '/dept/inbox?flash=ticket_reassigned',
        );
    }

    public function saveActionPlan(Request $request, string $reference): RedirectResponse
    {
        $files = $request->file('attachments', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $files = array_values(array_filter(is_array($files) ? $files : []));

        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->saveActionPlan($ticket, $user, $input, $files),
            null,
            function ($ticket) use ($request, $reference) {
                $submit = in_array($request->input('submitForReview'), [true, 1, '1', 'true'], true);
                if ($submit) {
                    $flash = $ticket->status === 'pending_president'
                        ? 'action_plan_submitted'
                        : 'action_plan_published';
                } else {
                    $flash = 'action_plan_saved';
                }

                return '/dept/tickets/'.rawurlencode($reference).'?flash='.$flash;
            },
        );
    }

    public function close(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->close($ticket, $user, $input),
            '/dept/tickets/'.rawurlencode($reference).'?flash=ticket_closed_dept',
        );
    }

    public function assignPersonnel(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->assignPersonnel($ticket, $user, $input),
            '/dept/tickets/'.rawurlencode($reference).'?flash=personnel_assigned',
        );
    }

    public function resolution(Request $request, string $reference): RedirectResponse
    {
        return $this->mutate(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->deptTickets->close($ticket, $user, $input),
            '/dept/tickets/'.rawurlencode($reference).'?flash=resolution_submitted',
        );
    }

    public function uploadDocuments(Request $request, string $reference): RedirectResponse
    {
        $user = $request->user();
        $ticket = $this->deptTickets->findForDeptHead($reference, $user);
        if (! $ticket) {
            return redirect()->away('/dept/tickets?flash=not_found');
        }

        $files = $request->file('attachments', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        $files = array_values(array_filter(is_array($files) ? $files : []));

        try {
            $ticket = $this->deptTickets->uploadDocuments($ticket, $user, $files);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to upload documents.';

            return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?error='.rawurlencode((string) $msg));
        }

        $this->orgMirror->syncTicket($ticket->toExpressArray());

        return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?flash=documents_uploaded_dept');
    }

    public function comment(Request $request, string $reference): RedirectResponse
    {
        $user = $request->user();
        $ticket = $this->threadComments->findAccessible($reference, $user);
        if (! $ticket) {
            return redirect()->away('/dept/tickets?flash=not_found');
        }

        try {
            $ticket = $this->threadComments->add($ticket, $user, $request->all());
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to post comment.';

            return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?error='.rawurlencode((string) $msg));
        }

        $this->orgMirror->syncTicket($ticket->toExpressArray());

        return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?flash=dept_comment_posted');
    }

    public function editComment(Request $request, string $reference): RedirectResponse
    {
        return $this->mutateThread($request, $reference, fn ($ticket, $user, $input) => $this->threadComments->edit($ticket, $user, $input), 'dept_comment_posted');
    }

    public function reactComment(Request $request, string $reference): RedirectResponse
    {
        $commentId = trim((string) $request->input('commentId', ''));

        return $this->mutateThread(
            $request,
            $reference,
            fn ($ticket, $user, $input) => $this->threadComments->react($ticket, $user, $input),
            null,
            $commentId !== '' ? '#comment-'.rawurlencode($commentId) : '',
        );
    }

    /**
     * @param  callable(\App\Models\RiskTicket, \App\Models\User, array<string, mixed>): \App\Models\RiskTicket  $op
     */
    private function mutateThread(
        Request $request,
        string $reference,
        callable $op,
        ?string $flash,
        string $hash = '',
    ): RedirectResponse {
        $user = $request->user();
        $ticket = $this->threadComments->findAccessible($reference, $user);
        if (! $ticket) {
            return redirect()->away('/dept/tickets?flash=not_found');
        }

        try {
            $ticket = $op($ticket, $user, $request->all());
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to update comment.';

            return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?error='.rawurlencode((string) $msg));
        }

        $this->orgMirror->syncTicket($ticket->toExpressArray());
        $query = $flash ? '?flash='.$flash : '';

        return redirect()->away('/dept/tickets/'.rawurlencode($reference).$query.$hash);
    }

    /**
     * @param  callable(\App\Models\RiskTicket, \App\Models\User, array<string, mixed>): \App\Models\RiskTicket  $op
     * @param  (callable(\App\Models\RiskTicket): string)|null  $successPathResolver
     */
    private function mutate(
        Request $request,
        string $reference,
        callable $op,
        ?string $successPath,
        ?callable $successPathResolver = null,
    ): RedirectResponse {
        $user = $request->user();
        $ticket = $this->deptTickets->findForDeptHead($reference, $user);
        if (! $ticket) {
            return redirect()->away('/dept/tickets?flash=not_found');
        }

        try {
            $ticket = $op($ticket, $user, $request->all());
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to update ticket.';

            return redirect()->away('/dept/tickets/'.rawurlencode($reference).'?error='.rawurlencode((string) $msg));
        }

        $this->orgMirror->syncTicket($ticket->toExpressArray());
        $path = $successPathResolver ? $successPathResolver($ticket) : (string) $successPath;

        return redirect()->away($path);
    }
}
