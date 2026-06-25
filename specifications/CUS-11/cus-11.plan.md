# Technical Specification: CUS-11 "Receiving parent can reject a swap request"

## Solution Architecture
Mirror of CUS-10. The receiving parent rejects a pending request via `POST /swap-requests/{id}/reject` with an optional comment. Rejection sets `status = rejected`; the `saving` hook nulls `active_date` (drops it from the pending queue + CUS-9 index), and since the effective-schedule overlay only applies **approved** swaps, the calendar is automatically unchanged. Notification delivery deferred to CUS-3.

### Acceptance criteria coverage
| AC | Approach |
|---|---|
| "Reject" action per incoming pending request | button beside "Approve" in `PendingRequests` |
| Optional comment before rejecting | shared comment field → `decision_comment` |
| Calendar unchanged on rejection | overlay applies only approved swaps (no-op for rejected) |
| Requesting parent notified | recorded for CUS-3 (delivery out of scope) |
| Rejected request removed from pending queue | `active_date`→null + CUS-9 index filters pending |

## Implementation Plan

### Phase 1 — Backend: reject transition
- [x] **Task 1: `[MODIFY] app/Services/SwapService.php`** — extract the shared guard+update from `approve()` into a private `decide(SwapRequest, string $deciderRole, ?string $comment, string $status)`; have `approve()` delegate (status approved) and add `reject()` delegating with status rejected.
- [x] **Task 2: `[MODIFY] app/Http/Controllers/SwapRequestController.php`** — add `reject(Request, SwapRequest, ParentResolver, SwapService)` mirroring `approve` (session-gate, role 403, optional comment validation, domain-exception mapping, 200 + updated request).
- [x] **Task 3: `[MODIFY] routes/web.php`** — `POST /swap-requests/{swapRequest}/reject`.
- [x] **Task 4: `[MODIFY] tests`** — unit (`SwapServiceTest`): reject sets status/comment + frees `active_date`; rejects non-pending; rejects decider-is-requester. Feature (`SwapRequestControllerTest`): 200 happy path (status rejected, dropped from index, **calendar unchanged**); 409; 403 requester & non-parent; 401; 404.

### Phase 2 — Frontend: reject action
- [x] **Task 5: `[MODIFY] frontend/src/api/swaps.js`** — `rejectSwapRequest(id, comment)` → `POST /swap-requests/{id}/reject`; surface server error.
- [x] **Task 6: `[MODIFY] frontend/src/pages/PendingRequests.jsx` (+ `.css`)** — refactor the item action area to support two actions: collapsed shows "Reject" + "Approve"; choosing either reveals the shared optional-comment field with a "Confirm <action>" button (Reject styled as a secondary/danger action). On success refetch + `onDecision()`. Mobile-first.

### Phase 3 — Verification
- [x] **Task 7:** `sail artisan test` (green) + `sail pint`; `npm run lint` + `npm run build`. Browser at :5174: reject an incoming request → it leaves the panel and the calendar day is unchanged; approve still works.

## Test Plan
- Unit: `SwapServiceTest::reject*` (+ refactor keeps approve tests green).
- Feature: `SwapRequestControllerTest::reject*`.
- Manual: Task 7.

## Security Considerations
- Session-gated; decider role resolved server-side; only the receiving parent may reject (403). Optional comment validated/length-capped. Route-model binding 404s unknown ids.

## Improvements (not in scope)
- Notification delivery (CUS-3). Own outgoing proposals (CUS-7).

## Changelog
| Date | Change |
|---|---|
| 2026-06-25 | Research + plan. Reject mirrors approve; shared `decide()` helper; calendar unchanged is automatic (overlay applies only approved). Notifications deferred to CUS-3. |
| 2026-06-26 | Implemented all phases. Backend: `SwapService::reject` + shared private `decide()` (also refactored `approve` and the controller to share a flow); `POST /swap-requests/{id}/reject`; 6 new tests (suite 60 green); Pint clean. Frontend: `rejectSwapRequest()`; PendingRequests item now offers Reject (danger) + Approve sharing the comment field; calendar refresh unchanged on reject. lint/build clean. Notification (req 4) deferred to CUS-3 (flagged). |
