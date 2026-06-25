# CUS-10 Research: Receiving parent can approve a swap request

## Linear issue
- ID: CUS-10 — Priority: Urgent — Size: Small (1-3)
- URL: https://linear.app/efficientweb/issue/CUS-10/receiving-parent-can-approve-a-swap-request

## Requirements (summary)
1. "Approve" action on each incoming pending request.
2. Optional comment before approving (the decider's note).
3. On approval, the calendar immediately reflects the custody change for the day.
4. Requesting parent is notified of the approval.
5. Approved request leaves the pending queue.

## How much already exists (post CUS-6/CUS-9)
- `SwapService::effectiveRoleFor()` / `applyToSchedule()` already overlay **approved** swaps onto the calendar — so once a request flips to `approved`, the calendar shows the change on next fetch (req 3). No calendar-side logic needed beyond a refresh.
- `SwapRequest` model: `STATUS_APPROVED` constant + `scopeApproved` already defined; the `saving` hook nulls `active_date` for non-pending rows, so approving **frees the day** from the unique-pending constraint (req 5 — and it also drops out of the CUS-9 index, which filters `pending`).
- `ParentResolver` resolves session email → role and `otherRole`.
- Frontend: `PendingRequests` panel lists incoming requests; `Calendar` is a sibling under `Home`. Both fetch independently.

## What CUS-10 adds
- **Decision comment**: new nullable `decision_comment` column on `swap_requests` (req 2). (`comment` stays the requester's note.)
- **Approve transition**: `SwapService::approve($request, $deciderRole, $comment)` — guards the request is pending and the decider is the **receiving** parent (not the requester), sets `status = approved` + `decision_comment`.
- **Endpoint**: `POST /swap-requests/{swapRequest}/approve` (session-gated, manual JSON per the api/* gotcha). 404 missing, 409 already-resolved, 403 if the caller is the requester / not a parent.
- **Frontend**: an "Approve" action per pending item with an optional comment field; on success, refetch the pending list (item disappears) **and** trigger a calendar refresh (sibling coordination via a small `version`/`onDecision` signal lifted to `Home`).

## Scope boundaries
- **Notifications (req 4)** — delivery is owned by CUS-3; consistent with CUS-6, CUS-10 only records the approval (the state CUS-3 will read). Confirm the same deferral.
- Reject is CUS-11 (will add a "Reject" action beside "Approve"); own-proposal views are CUS-7.

## Authorization
Only the **receiving** parent may approve: `deciderRole != requested_by_role`. Role resolved server-side from the session; never trusted from the client. A parent cannot approve their own proposal.

## Testability
- Unit: `approve()` sets status/comment, frees `active_date`; rejects non-pending; rejects when decider is the requester.
- Feature: approve endpoint happy path (200 + status approved, dropped from index, calendar day flips); 404; 409 already resolved; 403 requester/non-parent; 401 unauth.
