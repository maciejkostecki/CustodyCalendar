# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Environment

Laravel 13 (PHP 8.3) backend + React 19 (Vite) frontend. The two apps run independently: Laravel via Docker (Sail) on port 80, React via Vite dev server on port 5174 (5173 is reserved by Sail).

Start the full stack:
```bash
./vendor/bin/sail up -d     # Laravel + MySQL + Redis
cd frontend && npm run dev  # React dev server
```

## Commands

**Backend (run inside Sail or with `./vendor/bin/sail`):**
```bash
./vendor/bin/sail artisan test                            # full test suite
./vendor/bin/sail artisan test --filter AuthControllerTest  # single test class
./vendor/bin/sail composer require <package>
./vendor/bin/pint                                         # PHP linter (Laravel Pint)
```

**Frontend (`frontend/` directory):**
```bash
npm run dev      # Vite dev server
npm run build    # production build
npm run lint     # ESLint
```

## Architecture

### Auth flow
Google OAuth is handled entirely server-side by Laravel Socialite. The React app never touches a token.

1. React navigates to `GET /auth/google/redirect` → Laravel redirects to Google
2. Google returns to `GET /auth/google/callback` → `AuthController::handleGoogleCallback`
3. Email checked against `ALLOWED_PARENT_1_EMAIL` / `ALLOWED_PARENT_2_EMAIL` (`.env`)
4. Allowed → session started, redirect to `FRONTEND_URL/?auth=ok`
5. Denied → redirect to `FRONTEND_URL/login?error=access_denied`
6. OAuth failure → redirect to `FRONTEND_URL/login?error=oauth_failed`
7. React calls `GET /me` on mount; 401 → `/login`, 200 → home

### Session
Server-side sessions stored in MySQL (`SESSION_DRIVER=database`). The session cookie is the only auth artifact; the frontend holds no tokens. All auth-reading routes (`/me`, `/logout`) must be in `routes/web.php` (web middleware group) — **not** `routes/api.php` — to get `StartSession` middleware.

### Custody domain
Two parents are modelled as roles **father** / **mother**, configured in `config/custody.php` (label, color, and the email that maps to each role). `ParentResolver` resolves a logged-in email → role and the opposite role.

- **Default schedule** (`CustodyScheduleService`): Mon/Tue = mother, Wed/Thu = father; Fri/Sat/Sun is a weekend block alternating weekly, anchored at `config('custody.anchor_date')` (anchor weekend = father). `threeWeekSchedule()` builds 21 days from Monday of the current week, flagging `isToday` / `isPast`.
- **Effective schedule** (`SwapService`): the default overlaid with **approved** `swap_requests` (a day's custodial parent flips to the swap's `to_role`). `applyToSchedule()` also flags `pending` days. `CalendarController` returns the effective schedule.
- **Swaps** (`SwapService::propose`): a swap is a single-day custody transfer for a **future** date. One pending request per day is enforced by the DB — `swap_requests.active_date` equals `date` while pending and is `NULL` otherwise (a model `saving` hook keeps it in sync; the unique index does the rest). Domain failures throw `SwapProposalException` subclasses carrying the HTTP status (422 past/today, 409 duplicate).
- **Dates/timezone**: "today" and the week boundary resolve in `config('custody.timezone')` (default `Europe/Warsaw`), not server UTC. Compare days as `Y-m-d` strings, not Carbon instants — mixing timezones drifts comparisons across day/week boundaries (bit us twice).

### Route split
- `routes/web.php` — all session-aware routes: OAuth, `/me`, `/logout`, `GET /calendar`, `POST /swap-requests`
- `routes/api.php` — reserved for stateless API endpoints (see JSON gotcha below)

**JSON rendering gotcha:** `bootstrap/app.php` scopes automatic JSON exception rendering to `api/*` paths only. Session-gated endpoints live in `web.php`, so they must return JSON themselves — validate with `Validator::make(...)` + an explicit `response()->json(..., 422)` rather than `$request->validate()` (which would redirect on web routes).

### Vite proxy
The React dev server proxies backend paths to `http://localhost` (port 80) so there are no CORS issues in development: `/api`, `/auth`, `/me`, `/logout`, `/calendar`, `/swap-requests`. **Add every new backend path here** — a missing entry makes the request hit the dev server and fail confusingly. Proxy changes require a dev-server restart. Production needs a reverse proxy or the same domain.

### Key env vars
```
GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI
ALLOWED_PARENT_1_EMAIL / ALLOWED_PARENT_2_EMAIL   # who may log in
PARENT_FATHER_EMAIL / PARENT_MOTHER_EMAIL         # which email holds each role
CUSTODY_TIMEZONE=Europe/Warsaw
FRONTEND_URL=http://localhost:5174
```

### Tests
- `tests/Feature/AuthControllerTest.php` mocks Socialite via `Mockery` and overrides env vars using `\Illuminate\Support\Env::getRepository()->set(...)` (plain `putenv` / `$_ENV` is not sufficient — Laravel caches env after boot).
- Date-dependent tests use `Carbon::setTestNow(...)`; tests that override custody config do `config()->set('custody.*', ...)` in `setUp`. DB-touching tests (`SwapService`, swap endpoints) use `RefreshDatabase`.

### Specs
Per-issue research + plan live in `specifications/CUS-N/` (`cus-N.research.md`, `cus-N.plan.md`) with a Changelog documenting decisions and deviations. Work is done on `feature/cus-N-...` branches (the Linear `gitBranchName`).
