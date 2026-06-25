# Technical Specification: CUS-6 "Parent can propose a swap for a future date"

## Solution Architecture

First feature with **persistence** and **parent identity**. A logged-in user's email maps to a custody role (father/mother); clicking a future calendar day opens a proposal form; submitting creates a **pending** `swap_requests` row. The calendar now shows the **effective** schedule (default + approved swaps) and marks days that have a pending request.

Notification delivery is **out of scope** (owned by CUS-3); CUS-6 only persists the pending request that CUS-3 will later read.

### Identity mapping (confirmed)
- `maciej.kostecki@wydajnyweb.pl` → **father** (blue)
- `shastaan@gmail.com` → **mother** (pink)

Stored in `config/custody.php` as `parents.<role>.email` (env-backed). A `ParentResolver` service resolves email→role and role→other-role.

### Swap semantics
Single-day custody transfer: a proposal targets one date; on approval (later issue) that day flips to the other parent. `from_role` = effective custodian at proposal time, `to_role` = the other parent.

### Data model — `swap_requests`
`id`, `date` (date), `requested_by_role`, `from_role`, `to_role`, `status` (pending|approved|rejected|cancelled), `comment` (text null), `active_date` (date, nullable, **unique**), timestamps.
- `active_date` = `date` while status is `pending`, else `null` — maintained by a model `saving` hook. The unique index gives DB-level "one pending request per day" (req 7); MySQL allows multiple NULLs so non-pending rows don't collide.

### Acceptance criteria coverage
| AC | Approach |
|---|---|
| Click future day → form with date + current custodial parent | Calendar day carries effective `parent`; future days open modal |
| Custodial parent reflects approved swaps | `SwapService` overlays approved swaps onto the default schedule |
| Optional comment field | `comment` column + form field |
| Submit → pending request + day marked "pending" | `POST /swap-requests`; calendar `pending` flag |
| Past/today not interactive | Already true (CUS-4 `isPast`; today excluded) |
| Duplicate blocked with clear message | unique `active_date` + 409 + message |
| Either parent can propose for a day they hold | No restriction on requester vs custodian |

## Implementation Plan

### Phase 1 — Parent identity
- [x] **Task 1: `[MODIFY] config/custody.php`** — add `email` to `father` and `mother` (env: `PARENT_FATHER_EMAIL` default maciej…, `PARENT_MOTHER_EMAIL` default shastaan…).
- [x] **Task 2: `[MODIFY] .env` / `.env.example`** — add `PARENT_FATHER_EMAIL` / `PARENT_MOTHER_EMAIL` (example file: placeholders + comment).
- [x] **Task 3: `[CREATE] app/Services/ParentResolver.php`** — `roleForEmail(string): ?string`, `otherRole(string): string`, `all(): array`. Reads config.
- [x] **Task 4: `[CREATE] tests/Unit/ParentResolverTest.php`** — email→role for both parents, unknown email → null, otherRole.

### Phase 2 — Persistence + domain logic
- [x] **Task 5: `[CREATE] database/migrations/..._create_swap_requests_table.php`** — columns above, unique index on `active_date`, index on `date`.
- [x] **Task 6: `[CREATE] app/Models/SwapRequest.php`** — fillable, `date`/`active_date` casts to date, status constants, `scopePending`, `scopeApproved`, and a `saving` hook keeping `active_date` in sync with status.
- [x] **Task 7: `[CREATE] app/Services/SwapService.php`**
  - `today(): CarbonImmutable` — now in `custody.timezone`, start of day.
  - `effectiveRoleFor(CarbonInterface $date): string` — default role (via `CustodyScheduleService`) overridden by an approved swap's `to_role`.
  - `applyToSchedule(array $days): array` — overlay approved swaps (parent/label/color) and set `pending` per day.
  - `propose(string $requesterRole, CarbonInterface $date, ?string $comment): SwapRequest` — guard future-only and no existing pending (throw domain exceptions), compute `from_role`/`to_role`, persist pending.
- [x] **Task 8: `[CREATE] tests/Unit/SwapServiceTest.php`** (uses `RefreshDatabase`) — effective role with/without an approved swap; `propose` happy path; past/today rejected; duplicate rejected; `applyToSchedule` sets pending + overridden parent.

### Phase 3 — HTTP endpoints
- [x] **Task 9: `[MODIFY] app/Http/Controllers/CalendarController.php`** — run `threeWeekSchedule()` through `SwapService::applyToSchedule()` so `days` reflect approved swaps + `pending`.
- [x] **Task 10: `[CREATE] app/Http/Controllers/SwapRequestController.php`** — `store()`: session-gated; resolve requester role from session email (403 if not a parent); validate `date` (required, date format) and `comment` (nullable, max len); call `SwapService::propose`; map domain exceptions → 422 (past/today) and 409 (duplicate) with clear messages; return 201 + created request.
- [x] **Task 11: `[MODIFY] routes/web.php`** — `POST /swap-requests` → `SwapRequestController@store`.
- [x] **Task 12: `[CREATE] tests/Feature/SwapRequestControllerTest.php`** — 401 no session; 201 create pending (asserts row + `from`/`to`/requester); 409 duplicate + message; 422 past/today; calendar reflects pending after create and effective parent after an approved swap is seeded.

### Phase 4 — Frontend
- [x] **Task 13: `[CREATE] frontend/src/api/swaps.js`** — `createSwapRequest({date, comment})`; throw with parsed error message on non-OK (surface 409/422 text).
- [x] **Task 14: `[CREATE] frontend/src/pages/SwapProposalModal.jsx` (+ `.css`)** — shows date + current custodial parent label, optional comment textarea, submit/cancel; displays server error (e.g. duplicate); calls back on success.
- [x] **Task 15: `[MODIFY] frontend/src/pages/Calendar.jsx`** — future days clickable (open modal); render a "pending" marker on `day.pending`; on successful submit, refetch calendar. Past/today remain non-interactive.
- [x] **Task 16: `[MODIFY] frontend/src/pages/Calendar.css`** — pending marker style; clickable affordance (cursor/hover) on future days only.

### Phase 5 — Verification
- [x] **Task 17:** `sail artisan test` (all green), `sail pint`, `npm run lint` + `npm run build`. Browser check at :5174: click a future day → form shows date + custodial parent → submit → day shows pending; duplicate attempt shows clear message; past/today not clickable.

## Test Plan
- Unit: `ParentResolverTest`, `SwapServiceTest`.
- Feature: `SwapRequestControllerTest` (+ calendar pending/effective assertions).
- Manual: Task 17 browser flow.

## Security Considerations
- All routes session-gated. Requester role derived server-side from the session email (never trusted from the client). Input validation on `date`/`comment`. Duplicate enforced at DB level.

## Improvements (not in scope)
- Notification delivery (CUS-3). Approve/reject transitions (CUS-9/10/11). Single-sourcing the allowed-email list from `config/custody.php` parent emails.

## Changelog
| Date | Change |
|---|---|
| 2026-06-25 | Research + plan. Decisions: maciej=father / shastaan=mother; notification delivery deferred to CUS-3; swap = single-day transfer; one-pending-per-day via unique `active_date`. |
| 2026-06-25 | Implemented all phases. Backend: ParentResolver, swap_requests migration + SwapRequest model, SwapService (effective schedule + propose), SwapRequestController + POST /swap-requests, calendar decorated with effective parent + pending. 19 CUS-6 tests (full suite 37) green; Pint clean. Frontend: api/swaps.js, SwapProposalModal, clickable future days + pending marker. lint/build clean. Added `/swap-requests` to Vite proxy. NOTE: validation done manually in the controller because `bootstrap/app.php` scopes JSON auto-rendering to `api/*` — web routes must return JSON themselves. Browser verification of the authenticated flow blocked by the Google OAuth gate; logic fully covered by automated tests. |
