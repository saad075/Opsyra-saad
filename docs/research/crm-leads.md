# Research: the Leads module — Krayin's actual design

Written 2026-10-02, before building Opsyra's Leads module. The user chose
Krayin (github.com/krayin/laravel-crm) specifically, after an earlier,
broader attempt referenced against Twenty instead was rejected as not what
they were looking for — this module is a closer, more literal port of one
specific Krayin package (`packages/Webkul/Lead`), not a from-scratch
redesign informed loosely by it.

**Design reference only — no code copied.** See the 2026-09-24 "External
design references" entry in `docs/decisions.md`: Krayin is MIT ("the only
permissive one... but still prefer re-deriving"). Clone:

| Clone | Upstream | Ref | Licence |
|---|---|---|---|
| `_reference/krayin-crm` | github.com/krayin/laravel-crm | `v2.2.6` | MIT |

## Krayin's Lead module traced through its own migration history

Krayin's `leads` table isn't one static schema — it's evolved since 2021.
Reading only the original `create_leads_table` migration would port a
design Krayin itself abandoned. The final, current shape (confirmed by
reading every `alter_*`/`modify_*` migration up to 2025, not just the
`create_*` ones):

- **Sources and types** (`lead_sources`, `lead_types`): trivial per-install
  lookup tables — just `id`, `name`, timestamps.
- **Pipelines** (`lead_pipelines`): `name` (unique since 2025), `is_default`,
  `rotten_days` (default 30 — how long a lead can sit with no movement
  before it's considered stale).
- **Pipeline stages** (`lead_pipeline_stages`): **this is the part that
  changed.** Originally a shared `lead_stages` lookup table (`code`, `name`,
  `is_user_defined`) referenced by `lead_pipeline_stages.lead_stage_id` —
  stages reusable across pipelines. Migrations `2021_09_30_154222` and
  `2021_11_11_180804` denormalized `code`+`name` directly onto
  `lead_pipeline_stages` and repointed `leads.lead_pipeline_stage_id` at a
  specific pipeline's stage row, not the shared lookup. The old
  `lead_stages` table is still present in Krayin's schema but no longer
  wired into the live path — vestigial. **Ported the current behaviour**:
  each pipeline owns its own independent stages (unique `code` and `name`
  per pipeline), no shared lookup table.
- **`leads` itself**: `title`, `description`, `lead_value` (`decimal(12,4)`),
  a **nullable boolean `status`** (null = open, true = won, false = lost —
  not a separate enum), `lost_reason`, `closed_at`, `expected_close_date`
  (added later), `person_id` (→ Krayin's Contact package), `lead_source_id`,
  `lead_type_id`, `lead_pipeline_id`, `lead_pipeline_stage_id`, `user_id`
  (owner). By 2024 every one of those FKs is nullable; `lead_source_id` and
  `lead_type_id` are `onDelete('restrict')` (can't delete a source/type
  still in use), `user_id` is `onDelete('set null')`.
- **`code`, not the display name, is what business logic reads** — stages
  named "Won"/"Closed Won"/whatever a tenant renames them to are
  recognised by `code = 'won'`. Confirmed from the stage table even having
  a `code` column at all, separate from `name`, from the start.

## Decisions made with the user before building

1. **Scope: the Leads module only.** Not Krayin's Contact (Person/
   Organization) package, not its Activity package, not Products/Quotes/
   Tags. `leads.person_id` is replaced with inline
   `contact_name`/`contact_email`/`contact_phone` columns on the lead
   itself — a deliberate reduction, not an oversight. (The earlier,
   rejected attempt built a separate Contacts concept; this one
   intentionally doesn't.)
2. **Full pipeline/stage/source/type model, not a fixed stage list.** This
   is specifically what distinguishes Krayin's design from the simpler
   fixed-`stage`-enum approach used in the earlier rejected attempt — the
   whole point of choosing Krayin as the reference this time.
3. **Backend API only.** Migrations, models, controllers, one permission,
   tests — verified by the automated suite and live API calls against the
   running dev app, same rigor as before. No Vue screens, no sidebar entry.

## Deliberate deviations from Krayin (and why)

- **Every FK on `leads` is `nullOnDelete`**, not Krayin's mix of
  `restrict`/`set null`/(originally) `cascade` — matches Opsyra's own
  house style elsewhere (`users.hierarchy_parent_id`,
  `companies.owner_id` in the earlier attempt): losing a pipeline, stage,
  source, type or owner should detach a lead, never destroy it or block
  deleting the thing it pointed to. The *application layer* still refuses
  to delete a pipeline/stage/source/type that's actually in use (409, "in
  use — deactivate or move leads first") — the DB-level `nullOnDelete` is
  a backstop, not the primary guard.
- **`lead_value` is `decimal(12,2)`**, not Krayin's `decimal(12,4)` —
  matches `staff_salaries`' money convention elsewhere in this codebase
  (2 dp, not 4).
- **`rotten_days`/staleness is approximated from the lead's own
  `updated_at`**, not a real last-activity timestamp — this phase has no
  activity log (no Contacts/Activity package ported), so `Lead::isRotten()`
  is a simplification, not Krayin's actual mechanism (which would read
  real activity records).
- **Sources/types gain `sequence`/`is_active`/`is_system`** columns Krayin
  doesn't have — matches Opsyra's own existing pattern for every other
  configurable list (`departure_reasons`, `pay_components`) rather than
  inventing a new one just for this module.
- **`created_by` added alongside `owner_id`** (Krayin only has `user_id`,
  doing double duty as creator and owner) — Opsyra's own audit convention
  (`staff_salaries.created_by`), letting someone create a lead on another
  person's behalf without also making them its owner.

## Outcome

Built as `App\Modules\Crm`: `lead_sources`, `lead_types`, `lead_pipelines`,
`lead_pipeline_stages`, `leads` — all tenant-scoped (`BelongsToTenant` +
RLS). One permission, `CRM-ManageLeads`, gates every write; listing is open
to any authenticated staff member (matches `HRMS-StaffManagement`'s shape
for Branches/Departments). Every new tenant seeds a default "Sales
Pipeline" (7 stages: New/Contacted/Qualified/Proposal/Negotiation/Won/Lost)
plus starter sources and types, via `CrmServiceProvider`'s `Tenant::created`
hook — same "new tenant gets sensible defaults" pattern as
`HrServiceProvider` (departure reasons, checklist templates). See
`docs/decisions.md`'s 2026-10-02 entry for the full schema and
`tests/Feature/Crm/` for the isolation + functional tests, including the
won/lost/reopen stage-transition logic.
