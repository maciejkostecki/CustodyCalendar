# CUS-2 Research: Parent can log out

## Linear issue
- ID: CUS-2
- Title: Parent can log out
- Priority: High
- URL: https://linear.app/efficientweb/issue/CUS-2/parent-can-log-out

## Acceptance criteria vs current implementation

| AC | Status | Evidence |
|----|--------|----------|
| "Log out" option visible when logged in | PARTIAL | `frontend/src/pages/Home.jsx:6` — button exists but label is "Sign out" |
| Clicking it ends session and redirects to login | IMPLEMENTED | `App.jsx:25-33`, `auth.js:9-11`, `AuthController.php:52-56`, `routes/web.php:15` |
| Protected page after logout redirects to login | IMPLEMENTED | `App.jsx:44-47,50`, `AuthController.php:44-48` |

## Only gap
`frontend/src/pages/Home.jsx` line 7: button reads "Sign out" — AC specifies "Log out". One-line label change.

## Backend test coverage
`tests/Feature/AuthControllerTest.php` already covers logout (session cleared, `{ok:true}` returned) and `/me` 401 after no session.
