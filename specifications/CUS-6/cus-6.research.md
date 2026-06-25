# CUS-6 Research: Parent can propose a swap for a future date

## Linear issue
- ID: CUS-6 — Priority: Urgent — Size: Medium (5-8)
- URL: https://linear.app/efficientweb/issue/CUS-6/parent-can-propose-a-swap-for-a-future-date

## Requirements (summary)
1. Clicking a **future** day opens a proposal form/modal.
2. Form shows the selected date + its **effective** current custodial parent (default + approved swaps).
3. Optional comment field.
4. Submit → creates a **pending** swap request + notifies the other parent.
5. Proposed day visually marked **"pending"** in the calendar.
6. Past day / today → not interactive (already true from CUS-4).
7. Duplicate pending request for a day is blocked with a clear message.
8. Either parent may propose a swap for a day they are *currently* custodial parent (voluntary transfer).

## What a "swap" means here
Per the issue text (and CUS-5 "the affected day's custodial parent updates"), a swap is a **single-day custody transfer**: the proposal targets one date; on approval that day flips to the other parent. Not a two-day exchange.

## Current state / what must change
- **No persistence yet.** Need a `swap_requests` table + model + write endpoint. Migrations dir only has the default users/cache/jobs tables.
- **No parent identity link.** Session stores `{email, name, avatar}`. `config/custody.php` defines `father`/`mother` (label, color, timezone) but does **not** map a role to an email. To know which role the logged-in user is, we must map each allowed email → role.
  - Allowed emails (from `.env`): `shastaan@gmail.com` (PARENT_1), `maciej.kostecki@wydajnyweb.pl` (PARENT_2).
- **Schedule is default-only.** `CustodyScheduleService::threeWeekSchedule()` computes the default rule. It must become the **effective** schedule (default overlaid with approved swaps) and expose a **pending** marker per day. This also delivers part of CUS-5.
- `CalendarController` returns `{days, parents}`; days will gain `parent`/`label`/`color` reflecting approved swaps, plus `pending` (+ enough info for the form: the current custodial role).

## Proposed data model — `swap_requests`
| column | type | notes |
|---|---|---|
| id | pk | |
| date | date | the custody day being swapped |
| requested_by_role | string | 'father' \| 'mother' (derived from session email) |
| from_role | string | effective custodial role at proposal time |
| to_role | string | the other role (proposed new custodian) |
| status | string | 'pending' \| 'approved' \| 'rejected' \| 'cancelled' (CUS-6 only creates 'pending'; later issues transition) |
| comment | text null | requester's optional note |
| timestamps | | |

- **Partial-unique** on `(date)` where `status = 'pending'` to enforce req 7 (MySQL: enforce in app + a unique index on `(date, status)` is insufficient since multiple non-pending allowed; use an app-level guard + a generated/unique strategy — see plan).

## Conventions to follow
- Backend: thin controllers, session-gated routes in `routes/web.php`, JSON responses, service classes under `app/Services`. Eloquent model under `app/Models`.
- Tests: Feature tests with `withSession(['user' => ...])`; `RefreshDatabase` for DB-touching tests; `Carbon::setTestNow()` for date control.
- Frontend: `src/api/*.js` wrappers, plain CSS, components under `src/pages`.

## Open decisions (confirm before planning)
1. **Parent ↔ email mapping** — which allowed email is Father vs Mother? Needed to resolve the logged-in user's role and the "other parent".
2. **Notification scope (req 4)** — full in-app + email notification is the subject of **CUS-3**. Recommend CUS-6 only creates the pending request (the data the notification will read), and defer actual delivery to CUS-3. Confirm.

## Testability
- Service: effective parent with/without an approved swap; pending marker.
- Endpoint: create pending (happy path), duplicate blocked (409 + message), past/today rejected, role derivation, auth gate.
