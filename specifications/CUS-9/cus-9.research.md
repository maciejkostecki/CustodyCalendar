# CUS-9 Research: Receiving parent can view pending swap requests

## Linear issue
- ID: CUS-9 — Priority: Urgent — Size: Small (1-3)
- URL: https://linear.app/efficientweb/issue/CUS-9/receiving-parent-can-view-pending-swap-requests

## Requirements (summary)
1. A dedicated section lists all **incoming pending** swap requests.
2. A badge/count shows how many await the logged-in parent's decision.
3. Each entry: affected date, current custodial parent, requester's comment (if any), submission date/time.
4. Accessible from the main navigation / calendar view.
5. Empty-state message when there are none.

## "Incoming" = requests this parent must decide
A swap is proposed by one parent; the **receiving** parent (the non-requester) decides. So incoming-for-me = `status = pending AND requested_by_role != myRole`. (CUS-6 req 8 allows proposing a swap for a day you currently hold; the receiver is still the other parent.)

## Current state (post CUS-6, all in place)
- `swap_requests` table + `SwapRequest` model (`scopePending`), `from_role`, `requested_by_role`, `comment`, `created_at`.
- `ParentResolver` resolves session email → role and `otherRole`.
- `SwapRequestController` already exists (`store`); session-gating pattern established. JSON must be returned manually (api/* auto-render gotcha).
- `config('custody.parents')` gives label/color per role.
- Frontend: single authenticated screen = `Home` rendering `Calendar`. No nav beyond the header (greeting + logout). `api/swaps.js` exists.

## Display fields
- affected date → `date`
- current custodial parent → `from_role` (label/color from config; captured at proposal time)
- comment → `comment` (nullable)
- submission date/time → `created_at` (ISO; formatted client-side)

## Approach (no blocking decisions)
- **Backend:** `GET /swap-requests` (index) → incoming pending for the logged-in parent, each decorated with parent label/color + requester label. Session-gated; manual JSON.
- **Frontend:** a `PendingRequests` panel shown on the Home screen with a count **badge** in the header (the header is the app's main nav today). Toggle/expand to list entries; empty-state copy when zero. Add `/swap-requests` GET to the Vite proxy (POST path already proxied — same prefix, so already covered).
- Out of scope: approving/rejecting (CUS-10/11), viewing one's own outgoing proposals (CUS-7).

## Testability
- Feature: index returns only pending requests addressed to the logged-in parent (excludes own-proposed, excludes resolved); empty array when none; 401 unauth; 403 non-parent; entry shape.
