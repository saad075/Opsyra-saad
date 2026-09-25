# Opsyra

Multi-tenant SaaS for small businesses — HRMS, admin and finance now, CRM later.

Opsyra is a new codebase, built by copying and repurposing code from two existing
Brainstormer products, **SLMS360** and **ToddlersApp**. It does not fork either
product; both stay untouched, and every ported module records its source in
[`docs/provenance.md`](docs/provenance.md).

## Architecture at a glance

- One codebase, one deployment per country (`us`, `uk`, `om`, `pk`). Country is
  configuration (`APP_COUNTRY`), not a fork.
- PostgreSQL, one shared database per country. Tenants (clients) are isolated by
  `tenant_id`, enforced in two layers: an application-level `BelongsToTenant`
  global scope, and Postgres row-level security.
- Tenant resolution is ported from ToddlersApp: a small global directory maps a
  client code to the client's country endpoint.
- Per-country platform tables (`tenants`, `subscriptions`, `plans`, `plan_features`,
  `tax_tables`, `super_admins`) sit outside tenant scoping.

See [`Design/Opsyra ADR-001.html`](Design/Opsyra%20ADR-001.html) for the full
architecture decision record.

## Repo layout

- `_reference/` — **read-only** clones of SLMS360 and ToddlersApp used as source
  material for porting. Gitignored; never modified, never pushed to. See
  [Working with `_reference`](#working-with-_reference) below.
- `docs/inventory.md` — module-by-module port inventory (Phase 1).
- `docs/provenance.md` — source repo + commit SHA for every ported module.
- `docs/decisions.md` — running log of decisions made during the port.
- `docs/deploy.md` — per-country deployment notes (Phase 4).

## Working with `_reference`

`_reference/slms360` and `_reference/toddlersapp` are throwaway, read-only clones
with their `origin` remote removed. Rules:

- Never write to, commit to, checkout, stash, reset, pull or push in these
  folders, and never run migrations/seeders or point them at real `.env` files.
- Code only ever flows **out** of `_reference` into Opsyra — never back in.
- If a clone goes stale, delete it and re-clone from the original repo; don't try
  to update it in place.

## Status

Bootstrapping — see `docs/decisions.md` for current phase.
