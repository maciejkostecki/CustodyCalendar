# Technical Specification: CUS-2 "Parent can log out"

## Solution Architecture

The logout capability is already fully implemented as part of CUS-1. The only deviation from the acceptance criteria is a label mismatch: the button reads "Sign out" instead of the AC-mandated "Log out".

### Acceptance criteria coverage

| Acceptance Criterion | Status | Source |
|---|---|---|
| A "Log out" option is visible in the UI when logged in | Satisfied | `frontend/src/pages/Home.jsx:7` — renders "Log out" |
| Clicking "Log out" ends the session and redirects to the login screen | Satisfied | `App.jsx:25-33` handleLogout + `routes/web.php:15` POST /logout |
| Accessing any protected page after logout redirects to the login screen | Satisfied | `App.jsx:42-50` route guard from CUS-1 |

## Implementation Plan

### Phase 1 — Correct the logout button label

- [x] **Task 1: `[MODIFY] frontend/src/pages/Home.jsx` — change button label from "Sign out" to "Log out"**

  **Definition of Done:**
  - [x] Button in `Home.jsx` renders the text `Log out`
  - [x] No occurrences of `Sign out` remain in `Home.jsx`
  - [x] `onClick` handler and all other button attributes are unchanged
  - [x] No new files, routes, components, or imports added

## Test Plan

No new tests required. Backend logout coverage from CUS-1 remains valid. No logic changed.

## Security Considerations

None. Text-label change only.

## Changelog

| Date | Change |
|---|---|
| 2026-06-22 | Initial plan created |
| 2026-06-24 | Verified all acceptance criteria already satisfied in the committed code; button label already reads "Log out" (no code change needed). Marked tasks complete. |
