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

### Route split
- `routes/web.php` — OAuth routes + `/me` + `/logout` (session-aware)
- `routes/api.php` — currently empty; reserved for stateless API endpoints

### Vite proxy
The React dev server proxies `/api`, `/auth`, `/me`, `/logout` to `http://localhost` (port 80) so there are no CORS issues in development. Production needs a reverse proxy or the same domain.

### Key env vars
```
GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI
ALLOWED_PARENT_1_EMAIL / ALLOWED_PARENT_2_EMAIL
FRONTEND_URL=http://localhost:5174
```

### Tests
`tests/Feature/AuthControllerTest.php` mocks Socialite via `Mockery` and overrides env vars using `\Illuminate\Support\Env::getRepository()->set(...)` (plain `putenv` / `$_ENV` is not sufficient — Laravel caches env after boot).
