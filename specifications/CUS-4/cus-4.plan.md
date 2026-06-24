# Technical Specification: CUS-4 "Parent can view the three-week custody calendar"

## Solution Architecture

The 21-day custody schedule is computed **server-side** (authoritative, reusable once approved swaps in CUS-5/6 override the default). A new auth-gated endpoint `GET /calendar` returns the three-week window as JSON; the React frontend renders it as the primary screen.

Parents are modeled as two roles — **father** and **mother** — each with a display label and a color, defined in `config/custody.php` (no DB table needed yet; the schedule is purely date-derived).

### Schedule rule
- Mon, Tue → father
- Wed, Thu → mother
- Fri, Sat, Sun → weekend block, alternating weekly. For the Friday of that week: `weeks = floor((friday - 2026-06-26)/7)`, even → father, odd → mother. Sat/Sun inherit their week's Friday assignment.

### Acceptance criteria coverage
| Acceptance Criterion | Approach |
|---|---|
| 21 days: current week + next two full weeks | Backend builds 21 days starting Monday of current week |
| Each day shows custodial parent name, color-coded per parent | API returns role + label + color; frontend renders consistently |
| Default alternating schedule computed from anchor | `CustodyScheduleService` with anchor 2026-06-26 = father |
| Calendar starts Monday of current week regardless of today | `Carbon::now()->startOfWeek(Monday)` |
| Past days in current week dimmed + non-interactive | API flags `isPast`; frontend styles dimmed + `pointer-events:none` |
| Today visually distinguished | API flags `isToday`; frontend highlights |

## Implementation Plan

### Phase 1 — Backend: schedule domain logic
- [x] **Task 1: `[CREATE] config/custody.php`** — define `anchor_date` (`2026-06-26`) and `parents` map: `father` → `{label:'Father', color:'#...'}`, `mother` → `{label:'Mother', color:'#...'}`. Use accessible, distinct colors.
- [x] **Task 2: `[CREATE] app/Services/CustodyScheduleService.php`**
  - `custodialParentFor(CarbonInterface $date): string` → `'father'|'mother'` per the rule above.
  - `threeWeekSchedule(?CarbonInterface $today = null): array` → 21 entries from Monday of the current week, each: `date` (`Y-m-d`), `weekday`, `parent` (role), `label`, `color`, `isToday`, `isPast`.
  - **DoD:** pure, no I/O; reads anchor/colors from config; `isPast` true only for days before today (today excluded); window always starts Monday.
- [x] **Task 3: `[CREATE] tests/Unit/CustodyScheduleServiceTest.php`** — use `Carbon::setTestNow()`. Assert: Mon/Tue=father, Wed/Thu=mother; anchor weekend (2026-06-26/27/28)=father; following weekend=mother; window length 21; first day is Monday; `isToday`/`isPast` flags correct.

### Phase 2 — Backend: calendar endpoint
- [x] **Task 4: `[CREATE] app/Http/Controllers/CalendarController.php`** — `index()`: if `!Session::has('user')` return 401 JSON (mirrors `AuthController::me`); else return `response()->json(['days' => $service->threeWeekSchedule(), 'parents' => config('custody.parents')])`. Inject `CustodyScheduleService`.
- [x] **Task 5: `[MODIFY] routes/web.php`** — add `Route::get('/calendar', [CalendarController::class, 'index'])` (web group for session). Import the controller.
- [x] **Task 6: `[CREATE] tests/Feature/CalendarControllerTest.php`** — 401 when no session; 200 with `days` (count 21) + `parents` when session set; spot-check a known date's parent and the `isToday` flag via `Carbon::setTestNow()`.

### Phase 3 — Frontend: calendar view
- [x] **Task 7: `[CREATE] frontend/src/api/calendar.js`** — `getCalendar()` → `fetch('/calendar', {credentials:'include'})`, returns parsed JSON or throws on non-OK.
- [x] **Task 8: `[CREATE] frontend/src/pages/Calendar.jsx`** — fetch on mount; render 3 week-rows of 7 day-cells (Mon–Sun). Each cell: date, weekday, parent label; background/border color-coded by `color`. Apply `today` highlight and `past` dimming (reduced opacity + `pointer-events:none`). Handle loading + error states.
- [x] **Task 9: `[MODIFY] frontend/src/pages/Home.jsx`** — render `<Calendar />` as the primary content; keep the greeting + "Log out" button (e.g. in a small header). No change to logout behavior.
- [x] **Task 10: `[CREATE] frontend/src/pages/Calendar.css`** (or extend `App.css`) — grid layout, parent color classes, `.today`, `.past` styles. Match existing plain-CSS convention.

### Phase 4 — Verification
- [x] **Task 11:** Run `sail artisan test` (unit + feature green) and `pint`. Run `npm run lint` + `npm run build` in `frontend/`. Verify in browser at http://localhost:5174 (logged in): 21 days, correct colors, today highlighted, current-week past days dimmed, schedule matches the anchor rule.

## Test Plan
- Unit: `CustodyScheduleServiceTest` (schedule rule + window + flags).
- Feature: `CalendarControllerTest` (auth gate + response shape + sample values).
- Manual: browser verification per Task 11.

## Security Considerations
- Endpoint is auth-gated by the existing session check; no parameters accepted (no injection surface). No PII beyond role labels.

## Improvements (not in scope)
- Move parent config to DB once swaps/persistence land (CUS-5/6).
- Local-timezone "today" handling if parents span timezones.

## Changelog
| Date | Change |
|---|---|
| 2026-06-24 | Initial research + plan created. Decisions: backend-computed schedule; Father/Mother role labels. |
| 2026-06-25 | Implemented all phases. Backend: `config/custody.php`, `CustodyScheduleService`, `GET /calendar` + tests (7 unit, 3 feature, all green). Frontend: `api/calendar.js`, `Calendar.jsx`/`.css`, integrated into `Home.jsx`. Pint + ESLint clean, production build passes. Browser verification of the authenticated view blocked by the Google OAuth gate (same as CUS-1); schedule + endpoint fully covered by automated tests; UI verified via faithful component render. |
| 2026-06-25 | Second review round: (1) "today" now resolves in `Europe/Warsaw` (config `custody.timezone`, env `CUSTODY_TIMEZONE`) instead of server UTC — fixes today highlighting the wrong day near midnight. Fixed a related parity bug (UTC anchor vs local date drift) by comparing plain calendar dates; added a timezone-stability regression test. (2) Removed the "Today" pill; current day now uses a white outline ringed by the parent color. |
| 2026-06-25 | Fixes after user review: (1) added `/calendar` to the Vite proxy (requests were bouncing off the dev server) and gave Home a padded, left-aligned container overriding the starter template's centered 56px h1. (2) **DEVIATION from written AC**: per user, weekday assignment swapped to Mon/Tue = Mother, Wed/Thu = Father (Linear ticket text says the opposite; user is authority — Linear AC should be updated to match). Tests updated. (3) Readability: added date-range title, Mon–Sun header row, month markers, a "Today" pill, and greyed-out (desaturated + faded) past days. |
