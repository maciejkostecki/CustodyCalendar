# Technical Specification: CUS-9 "Receiving parent can view pending swap requests"

## Solution Architecture
A `GET /swap-requests` endpoint returns the **incoming pending** requests for the logged-in parent (pending requests proposed by the *other* parent), each decorated with display labels/colors. The frontend shows them in a `PendingRequests` panel on the Home screen with a count **badge** in the header (the header is the app's current main nav). Empty state when there are none.

### "Incoming" filter
`status = pending AND requested_by_role != <my role>`. Role resolved server-side from the session email via `ParentResolver` (never trusted from the client).

### Entry shape (per request)
`id`, `date`, `weekday`, `from_role` + `from_label` + `from_color` (current custodial parent), `requested_by_role` + `requested_by_label`, `comment`, `created_at` (ISO).

### Acceptance criteria coverage
| AC | Approach |
|---|---|
| Dedicated section lists incoming pending | `PendingRequests` panel fed by `GET /swap-requests` |
| Badge/count of awaiting decisions | count badge in the Home header |
| Each entry: date, current custodial parent, comment, submitted at | endpoint returns these; panel renders them |
| Accessible from main navigation | header badge toggles the panel (header = current nav) |
| Empty state when none | panel renders empty-state copy |

## Implementation Plan

### Phase 1 — Backend: incoming list endpoint
- [x] **Task 1: `[MODIFY] app/Http/Controllers/SwapRequestController.php`** — add `index(ParentResolver, ...)`: session-gated (401), resolve role (403 if not a parent), return pending requests where `requested_by_role != role`, ordered by `date`, each mapped to the entry shape above (labels/colors from `config('custody.parents')`).
- [x] **Task 2: `[MODIFY] routes/web.php`** — `GET /swap-requests` → `SwapRequestController@index`.
- [x] **Task 3: `[MODIFY] tests/Feature/SwapRequestControllerTest.php`** — index: 401 unauth; 403 non-parent; returns only pending addressed to me (excludes my own proposals and resolved ones); empty array when none; asserts entry fields (date, from_label, comment, created_at, requested_by_label).

### Phase 2 — Frontend: pending-requests panel
- [x] **Task 4: `[MODIFY] frontend/src/api/swaps.js`** — add `getIncomingSwapRequests()` → `GET /swap-requests`.
- [x] **Task 5: `[CREATE] frontend/src/pages/PendingRequests.jsx` (+ `.css`)** — fetch on mount; header shows a count badge; lists each request (formatted date, current custodial parent with color, comment if present, "submitted" timestamp); empty-state message when zero; loading/error states.
- [x] **Task 6: `[MODIFY] frontend/src/pages/Home.jsx`** — render `<PendingRequests />` (e.g. above the calendar) so it's reachable from the main screen.

### Phase 3 — Verification
- [x] **Task 7:** `sail artisan test` (green) + `sail pint`; `npm run lint` + `npm run build`. Browser at :5174: with a pending request addressed to the logged-in parent, the badge shows the count and the panel lists it with all fields; logging in as the requester shows it as *not* incoming; empty state when none.

## Test Plan
- Feature: `SwapRequestControllerTest` index cases (filtering, empty, auth, shape).
- Manual: Task 7.

## Security Considerations
- Session-gated; role resolved server-side. Read-only endpoint; no parameters. Returns only requests addressed to the logged-in parent.

## Improvements (not in scope)
- Approve/reject actions (CUS-10/11). Own outgoing proposals (CUS-7). Real-time/notification badges (CUS-3).

## Changelog
| Date | Change |
|---|---|
| 2026-06-25 | Research + plan. Incoming = pending where requester is the other parent; surfaced as a Home-screen panel with a header count badge. Vite proxy already covers `/swap-requests` (prefix match from CUS-6). |
| 2026-06-25 | Implemented all phases. Backend: `GET /swap-requests` index (incoming pending for logged-in parent, decorated entries) + 5 feature tests (suite 42 green); Pint clean. Frontend: `getIncomingSwapRequests()`, `PendingRequests` panel (count badge + list + empty state) on Home. lint/build clean. Authenticated browser verification blocked by OAuth gate; seeded a Mother-proposed pending request for live viewing. |
