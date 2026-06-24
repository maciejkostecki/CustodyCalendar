# Technical Specification: CUS-2 "Parent can log out"

## Solution Architecture

The logout capability is already fully implemented as part of CUS-1. The only deviation from the acceptance criteria is a label mismatch: the button reads "Sign out" instead of the AC-mandated "Log out".

### Acceptance criteria coverage

| Acceptance Criterion | Status | Source |
|---|---|---|
| A "Log out" option is visible in the UI when logged in | Needs label fix | `frontend/src/pages/Home.jsx:7` — currently "Sign out" |
| Clicking "Log out" ends the session and redirects to the login screen | Already satisfied | Existing logout handler from CUS-1 |
| Accessing any protected page after logout redirects to the login screen | Already satisfied | Existing protected-route guard from CUS-1 |

## Implementation Plan

### Phase 1 — Correct the logout button label

- [ ] **Task 1: `[MODIFY] frontend/src/pages/Home.jsx` — change button label from "Sign out" to "Log out"**

  **Definition of Done:**
  - [ ] Button in `Home.jsx` renders the text `Log out`
  - [ ] No occurrences of `Sign out` remain in `Home.jsx`
  - [ ] `onClick` handler and all other button attributes are unchanged
  - [ ] No new files, routes, components, or imports added

## Test Plan

No new tests required. Backend logout coverage from CUS-1 remains valid. No logic changed.

## Security Considerations

None. Text-label change only.

## Changelog

| Date | Change |
|---|---|
| 2026-06-22 | Initial plan created |
