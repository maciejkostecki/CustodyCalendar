# Technical Specification: CUS-5 "Calendar reflects approved swaps"

## Solution Architecture
No new feature. The capability was delivered incrementally and is fully present on `main`:

- **Req 2 (color reflects new parent)** — `SwapService::applyToSchedule` overrides an approved day's `parent`/`label`/`color` to the swap's `to_role`; `CalendarController` serves this. (CUS-6)
- **Req 1 (immediate, no reload)** — `Home` holds a `version` signal bumped by `PendingRequests` on approval; `Calendar` refetches when it changes. (CUS-10)

CUS-5 is therefore a verification + documentation pass.

### Acceptance criteria coverage
| AC | Status | Evidence |
|---|---|---|
| Approved day updates without a page reload | Satisfied | `Home.jsx` `version` signal → `Calendar.jsx` refetch (CUS-10) |
| Updated day's color reflects new custodial parent | Satisfied | `SwapService::applyToSchedule` overrides parent/label/color; locked by `test_calendar_reflects_approved_swap` (now asserts label + color) |

### Scope note
"Immediate" applies to the approving parent (their calendar refetches). A second parent with the calendar open elsewhere updates on next reload — real-time cross-client push is out of scope (overlaps CUS-3). User story is approver-centric, so satisfied.

## Implementation Plan
- [x] **Task 1: Verify both criteria against current code** (effective overlay + version-signal refresh).
- [x] **Task 2: `[MODIFY] tests/Feature/SwapRequestControllerTest.php`** — extend `test_calendar_reflects_approved_swap` to assert the approved day's `label` and `color` (not just `parent`), locking req 2.
- [x] **Task 3: Document** in this spec + the changelog; mark the Linear AC.

## Test Plan
- Feature: `test_calendar_reflects_approved_swap` (parent + label + color + not-pending). Green.
- Manual (already exercised in CUS-10): approving an incoming request flips the day's color live, no reload.

## Security Considerations
None — no code or surface change beyond a test assertion.

## Changelog
| Date | Change |
|---|---|
| 2026-06-26 | Verified CUS-5 is already delivered by CUS-6 (effective overlay) + CUS-10 (immediate refresh). Added label/color assertions to the calendar feature test. No production change. Cross-device real-time sync noted as out of scope. |
