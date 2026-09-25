# Decisions log

Running log of choices made during the Opsyra port. Newest entries at the top.

## 2026-09-24 — Bootstrap

- **SLMS360 source**: tag `v2.7.1` from the local clone at
  `Software/SLMS360` (origin `brainstormertech/slms360`, commit `fa18b472`).
  This tag postdates and supersedes the "v2.5.0" module set referenced in
  ADR-001 — it includes a rebuild of Task/Project Management with tenant,
  branch and permission scoping (Task/Project itself stays **excluded** per
  scope; the rebuild is only relevant as the source of the current security
  baseline).
- **ToddlersApp source**: `master` branch from the local clone at
  `Software/toddlersapp` (origin `brainstormertech/toddlersapp`, commit
  `a22725a9`). The prompt referenced a branch "Main", which doesn't exist;
  confirmed with the user that `master` (the default branch) is correct.
  The working copy on disk also has a checked-out branch `Toddler-Kashif`
  with uncommitted local edits to student-fee code — irrelevant here since
  clone only pulls committed history, and `master` was the chosen ref anyway.
- **SLMS360 mobile app** (`slms360_management_app` / `SLMSAPP-Staff`, Flutter
  staff/attendance app): found locally but **not** added as a reference
  input for Phase 1. Confirmed with the user — HR/attendance/ID-card scope
  is inventoried as backend (API) only for now; mobile port deferred.
- Reference clones live in `_reference/slms360` and `_reference/toddlersapp`,
  cloned from the local repos above with `origin` removed. Gitignored.
