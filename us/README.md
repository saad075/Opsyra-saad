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

## Local development

**Docker Compose** (`docker-compose.yml`, `docker/`) is the intended path —
`docker compose up`, then `docker compose exec app php artisan migrate`. This
has not been exercised on the machine this was built on (no Docker install
there); review before first use.

**Native setup** (what this was actually built and tested against):

1. PostgreSQL 17 and a Redis-compatible server running locally (on Windows,
   [Memurai](https://www.memurai.com/) — the official Redis Windows port is
   abandoned since 2016).
2. Create a role and per-country databases:
   ```sql
   CREATE ROLE opsyra WITH LOGIN PASSWORD '<password>' CREATEDB;
   CREATE DATABASE opsyra_us OWNER opsyra;   -- repeat for uk / om / pk as needed
   CREATE DATABASE opsyra_us_test OWNER opsyra; -- test suite uses Postgres too, for RLS
   ```
3. `composer install`, copy `.env.example` to `.env`, fill in the DB/Redis
   credentials above, `php artisan key:generate`, `php artisan migrate`.
4. `php artisan serve`.

See `docs/decisions.md` (2026-09-25 entry) for why native was used here and
what to watch for switching back to Docker.

## Status

Bootstrapping — see `docs/decisions.md` for current phase.
