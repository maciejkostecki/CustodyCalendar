# CUS-11 Research: Receiving parent can reject a swap request

## Linear issue
- ID: CUS-11 — Priority: Urgent — Size: Small (1-3)
- URL: https://linear.app/efficientweb/issue/CUS-11/receiving-parent-can-reject-a-swap-request

## Requirements (summary)
1. "Reject" action on each incoming pending request.
2. Optional comment before rejecting.
3. On rejection, the calendar is unchanged.
4. Requesting parent is notified of the rejection.
5. Rejected request leaves the pending queue.

## This is the mirror of CUS-10 (approve)
Everything is already in place from CUS-10; reject differs only in the target status:
- `SwapRequest::STATUS_REJECTED` constant already exists.
- The `saving` hook nulls `active_date` for any non-pending status → rejected requests **leave the pending queue** (req 5) and drop from the CUS-9 index.
- The effective-schedule overlay (`applyToSchedule`) only applies **approved** swaps, so a rejected request has **no calendar effect** (req 3 — calendar unchanged) automatically.
- Guards (`RequestNotPendingException` 409, `NotTheReceivingParentException` 403) and `decision_comment` already exist.
- `approve()` and the controller's `approve()` are the template; reject is identical bar the status.

## What CUS-11 adds
- **Backend:** `SwapService::reject($request, $deciderRole, $comment)` — same guards as approve, sets `status = rejected`. Extract the shared guard+transition into a private `decide(...)` used by both `approve` and `reject` (DRY).
- **Endpoint:** `POST /swap-requests/{swapRequest}/reject`.
- **Frontend:** a "Reject" action beside "Approve" on each pending item, sharing the optional-comment field; on success refetch the list and refresh the calendar (calendar won't change for reject, but the item disappears).

## Scope boundaries
- Notification delivery (req 4) → CUS-3 (already flagged); reject only records the decision.

## Testability
- Unit: `reject()` sets rejected + comment, frees `active_date`; rejects non-pending (409) and decider-is-requester (403).
- Feature: reject endpoint 200 happy path (status rejected, dropped from index, **calendar unchanged**); 409 already-decided; 403 requester/non-parent; 401 unauth; 404 missing.
