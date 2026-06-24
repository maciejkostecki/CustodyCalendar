# CUS-4 Research: Parent can view the three-week custody calendar

## Linear issue
- ID: CUS-4
- Title: Parent can view the three-week custody calendar
- Priority: Urgent
- Size: Large (13+)
- URL: https://linear.app/efficientweb/issue/CUS-4/parent-can-view-the-three-week-custody-calendar

## Requirements (verbatim summary)
1. Show current week + next two full weeks = 21 days.
2. Each day shows custodial parent's name, color-coded consistently per parent.
3. Default schedule:
   - Mon, Tue = Father
   - Wed, Thu = Mother
   - Fri, Sat, Sun = alternate **every week**, anchored to Fri 2026-06-26 / Sat 2026-06-27 / Sun 2026-06-28 = **Father**.
4. Calendar always starts on **Monday of the current week**, even mid-week.
5. Past days in the current week are shown but **dimmed and non-interactive**.
6. Today is **visually distinguished**.

## Schedule computation (deterministic, no persistence needed yet)
For any date:
- Mon/Tue → Father
- Wed/Thu → Mother
- Fri/Sat/Sun → belong to the **Friday-anchored weekend block** of that calendar week.
  - Find the Friday of that week (Fri/Sat/Sun all share one weekend assignment).
  - `weeks = floor((thatFriday - 2026-06-26) / 7 days)`
  - even → Father, odd → Mother.

Anchor week (Fri 2026-06-26) weekend = Father; following week = Mother; alternating thereafter.
Works for weekends before the anchor too (negative diff, floor keeps parity correct).

## Current codebase state
- **No calendar code exists.** `frontend/src/pages/Home.jsx` is a placeholder (name + logout button).
- Backend: only `AuthController` (Socialite + session). No domain models beyond the default `User`.
- Auth is **session-based**; `Session::has('user')` gates `/me`. Any new auth-gated endpoint must live in `routes/web.php` (web middleware group) — `routes/api.php` is stateless and currently empty (per CLAUDE.md).
- Frontend routing: `App.jsx` guards `/` on `status === 'authenticated'`, renders `Home`. API calls go through `frontend/src/api/*.js` using `fetch(..., {credentials:'include'})` and the Vite proxy.
- No date library present on the frontend; backend has Carbon (Laravel).

## Conventions observed
- Backend: thin controllers, `env()` for config, JSON responses, `Session` facade.
- Frontend: functional components, one file per page under `src/pages`, API wrappers under `src/api`, plain CSS.
- Tests: `tests/Feature/*` with Mockery; env overridden via `Env::getRepository()->set(...)`.

## Open design decisions (to confirm with user before planning)
1. **Where to compute the schedule** — backend endpoint (authoritative, reusable for future swap features CUS-5/6) vs. pure frontend. Recommendation: **backend**, since approved swaps (CUS-5+) will override the default schedule server-side.
2. **Parent identity / naming** — display the role labels "Father"/"Mother", or real parent names? And how do parents map to the two allowed emails? Recommendation: define two parents in backend config (role, display name, color); default display names "Father"/"Mother".

## Testability
- Pure date→parent function is unit-testable: assert anchor weekend = Father, next weekend = Mother, Mon/Tue/Wed/Thu fixed, and the 21-day window starts on Monday.
