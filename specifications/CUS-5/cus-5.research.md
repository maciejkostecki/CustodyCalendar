# CUS-5 Research: Calendar reflects approved swaps

## Linear issue
- ID: CUS-5 — Priority: Urgent — Size: Small (1-3)
- URL: https://linear.app/efficientweb/issue/CUS-5/calendar-reflects-approved-swaps

## Requirements / AC
1. When a swap is approved, the affected day updates in the calendar **without a page reload**.
2. The updated day's **color** reflects the new custodial parent.

## Already delivered by prior issues
This capability was built incrementally and is fully present on `main`:

- **Effective schedule overlay (CUS-6, `SwapService::applyToSchedule`)** — for any approved swap on a day, it overrides that day's `parent`, `label`, and `color` to the swap's `to_role`. `CalendarController` returns this decorated schedule. → satisfies req 2.
- **Immediate refresh on approval (CUS-10)** — `Home` holds a `version` signal; `PendingRequests` calls `onDecision()` after a successful approval, bumping `version`; `Calendar` lists `version` in its load effect deps and refetches. No page reload. → satisfies req 1 for the approving parent.
- **Existing test** — `tests/Feature/SwapRequestControllerTest::test_calendar_reflects_approved_swap` asserts the approved day's `parent` flips and `pending` is false.

## Gap analysis
No production code change needed. Only verification + an explicit assertion that the day's **color/label** (not just role) reflect the new parent — req 2 is about color specifically and the current test only checks `parent`.

## Scope note (cross-device)
The "immediate, no-reload" update applies to the parent who performs the approval (their calendar refetches). A second parent with the calendar already open on another device won't see the change until they reload — real-time cross-client push (websockets/polling) is out of scope and overlaps with CUS-3 notifications. The user story is written from the approver's perspective, so CUS-5 is considered satisfied.

## Plan
Verify-only: add a color/label assertion to the existing feature test, document, mark the Linear AC. No new feature.
