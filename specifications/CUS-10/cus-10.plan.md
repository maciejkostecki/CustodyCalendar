# Technical Specification: CUS-10 "Receiving parent can approve a swap request"

## Solution Architecture
Add an approve transition to a pending swap request. The receiving parent (non-requester) approves via `POST /swap-requests/{id}/approve` with an optional decision comment. Approval sets `status = approved`; the existing effective-schedule overlay then shows the custody change, the request leaves the pending queue automatically (the `saving` hook nulls `active_date` and the CUS-9 index filters `pending`), and the frontend refreshes both the pending list and the calendar.

Notification delivery is deferred to CUS-3 (consistent with CUS-6) — CUS-10 records the approval only.

### Authorization
Only the receiving parent may approve: server resolves the caller's role from the session and requires `role != requested_by_role`. A parent cannot approve their own proposal.

### Acceptance criteria coverage
| AC | Approach |
|---|---|
| "Approve" action per incoming pending request | button on each `PendingRequests` item |
| Optional comment before approving | inline comment field → `decision_comment` |
| Approval immediately updates the calendar | status→approved + calendar refresh; effective overlay already exists |
| Requesting parent notified | recorded for CUS-3 (delivery out of scope) |
| Approved request removed from pending queue | `active_date`→null + CUS-9 index filters pending |

## Implementation Plan

### Phase 1 — Backend: approve transition
- [x] **Task 1: `[CREATE] migration` add `decision_comment` (text, nullable) to `swap_requests`.**
- [x] **Task 2: `[MODIFY] app/Models/SwapRequest.php`** — add `decision_comment` to `$fillable`.
- [x] **Task 3: `[MODIFY] app/Services/SwapService.php`** — `approve(SwapRequest $request, string $deciderRole, ?string $comment): SwapRequest`: throw if not pending (`SwapProposalException` 409) or if `deciderRole === $request->requested_by_role` (new 403 exception); set `status = approved` + `decision_comment`; save; return.
- [x] **Task 4: `[CREATE] app/Exceptions/NotTheReceivingParentException.php`** (extends `SwapProposalException`, status 403).
- [x] **Task 5: `[MODIFY] app/Http/Controllers/SwapRequestController.php`** — `approve(Request, SwapRequest $swapRequest, ParentResolver, SwapService)`: session-gate (401); resolve role (403 non-parent); validate optional `comment` (nullable, max 1000); guard already-resolved (409); call `SwapService::approve`; return 200 + updated request. Use route-model binding; manual JSON validation.
- [x] **Task 6: `[MODIFY] routes/web.php`** — `POST /swap-requests/{swapRequest}/approve`.
- [x] **Task 7: `[MODIFY] tests`** — unit (`SwapServiceTest`): approve sets status/comment + frees `active_date`; rejects non-pending; rejects when decider is requester. Feature (`SwapRequestControllerTest`): 200 happy path (status approved, calendar day flips, dropped from index); 409 already-resolved; 403 requester & non-parent; 401 unauth; 404 missing.

### Phase 2 — Frontend: approve action
- [x] **Task 8: `[MODIFY] frontend/src/api/swaps.js`** — `approveSwapRequest(id, comment)` → `POST /swap-requests/{id}/approve`; surface server error message.
- [x] **Task 9: `[MODIFY] frontend/src/pages/PendingRequests.jsx` (+ `.css`)** — each item gets an "Approve" button that reveals an optional comment field + confirm; on success refetch the list and call `onDecision()`; show server errors. Mobile-first (full-width controls on narrow screens).
- [x] **Task 10: `[MODIFY] frontend/src/pages/Home.jsx` + `Calendar.jsx`** — lift a `version` signal in `Home`; `PendingRequests` calls `onDecision` to bump it; `Calendar` reloads when the signal changes (so the approved day updates immediately).

### Phase 3 — Verification
- [x] **Task 11:** `sail artisan test` (green) + `sail pint`; `npm run lint` + `npm run build`; migrate dev DB. Browser at :5174: approve an incoming request → it leaves the panel and the calendar day flips to the new parent; try approving a non-incoming/own request is not offered; (re)verify the CUS-6 effective-parent criterion now reachable.

## Test Plan
- Unit: `SwapServiceTest::approve*`.
- Feature: `SwapRequestControllerTest::approve*`.
- Manual: Task 11 (also closes the flagged CUS-6 effective-parent check).

## Security Considerations
- Session-gated; decider role resolved server-side; only the receiving parent may approve (403 otherwise). Optional comment validated/length-capped. Route-model binding 404s unknown ids.

## Improvements (not in scope)
- Notification delivery (CUS-3). Reject (CUS-11). Own outgoing proposals (CUS-7).

## Changelog
| Date | Change |
|---|---|
| 2026-06-25 | Research + plan. Approve = status transition by the receiving parent; decision_comment added; calendar/pending refresh via a lifted version signal; notifications deferred to CUS-3. |
| 2026-06-25 | Implemented all phases. Backend: decision_comment migration, SwapService::approve (guards pending + receiver-only), NotTheReceivingParent/RequestNotPending exceptions, POST /swap-requests/{id}/approve with route-model binding. 9 new tests (suite 51 green); Pint clean. Frontend: approveSwapRequest(), inline Approve action + optional comment in PendingRequests (mobile-first), and a lifted version signal so the Calendar refetches on approval. lint/build clean. Authenticated browser verification blocked by OAuth gate; logic fully tested. This also makes the flagged CUS-6 effective-parent criterion reachable end-to-end. |
