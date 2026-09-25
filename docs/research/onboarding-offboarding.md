# Research: onboarding / offboarding — how other HR systems do it

Written 2026-09-24, before porting SLMS360's onboarding/offboarding approval
module (Phase 3, module 3). Purpose: check SLMS360's design against mature
open-source HR systems before building, so we port deliberately rather than
copy whatever SLMS360 happened to have.

**Design reference only — no code copied.** See the 2026-09-24 "External
design references" entry in `docs/decisions.md` for the licence policy.
Clones are in `_reference/` (gitignored), sparse, at these commits:

| Clone | Upstream | Ref | Licence |
|---|---|---|---|
| `_reference/frappe-hrms` | github.com/frappe/hrms | `develop` @ `aa1ec5a` | GPL-3.0 |
| `_reference/horilla` | github.com/horilla-opensource/horilla | `2.0` @ `364ab2b` | LGPL-2.1 |
| `_reference/odoo` | github.com/odoo/odoo | `20.0` @ `f254c79` | LGPL-3.0 (Community) |

## What each one does

### SLMS360 (what we're porting)

An **approval gate**, not a process. One request row per event:

- `staff_onboarding_requests`: pending → approved | rejected. If the creator
  holds `HRMS-Approval-L2` it auto-approves. Rejection does *not* delete the
  staff record — HR must "complete" the rejection (confirm the candidate was
  told), and only then does the user move to `offboarded`.
- `staff_offboard_requests`: pending → approved | rejected, with
  `last_working_day`, `reason`, and a **required replacement manager if the
  leaver has active direct reports**. Future-dated exits keep the person
  active; nightly `staff:process-offboarding` flips them and reassigns
  reports then. `cancelOffboarding` undoes a not-yet-effective exit.
- Nightly `staff:activate-pending` activates future-dated joiners.
- No checklist, no tasks, no templates.

### Frappe HRMS

A **task checklist**, not an approval gate:

- `Employee Onboarding Template` / `Employee Separation Template` — reusable
  lists of activities, optionally keyed by department/designation/grade.
- Each activity: name, assigned **user or role**, `begin_on` (days offset from
  start), `duration`, `required_for_employee_creation`.
- Submitting an onboarding creates a Project + one Task per activity,
  assigned to the named user or everyone holding the role; dates skip
  holidays. `boarding_status` (Pending / In Process / Completed) is derived
  from task completion.
- The Employee record can't be created until every activity flagged
  `required_for_employee_creation` is done — a checklist acting as a gate.
- Separation has a separate **Exit Interview** (Pending / Scheduled /
  Completed / Cancelled; outcome *Employee Retained* / *Exit Confirmed*) and
  a **Full and Final Statement** (payables, receivables, assets to
  return/recover, posts a journal entry).

### Horilla

**Stage pipelines** (kanban-style):

- Onboarding: stages (sequence, managers, `is_final_stage`) → tasks per stage
  (`is_required`) → per-candidate task status (todo / scheduled / ongoing /
  stuck / done). A tokenised **self-service portal** lets the new hire upload
  documents before day one.
- Offboarding: named pipelines with typed stages — *notice period*,
  *interview*, *work handover*, *FnF settlement*, *other*, *archived*
  (reaching *archived* deactivates the employee). Per-employee notice period
  (value + unit, start/end dates), notes with attachments, exit reasons.
- **Resignation letter**: employee-initiated (requested / approved /
  rejected), gated by a company-level setting.

### Odoo (Community)

The **simplest** model:

- `hr.departure.reason` — a configurable list (fired / resigned / retired
  seeded and undeletable), **scoped per country**.
- A departure records dismissal date + departure date + "archive on" date.
  A daily cron applies departures whose date has passed (archives the
  employee, and the login user only if they have no other active employee
  record). Same "future-dated exit applied by cron" idea as SLMS360.
- Onboarding/offboarding checklists are generic **activity plans**
  (seeded: "set up IT materials", "training", "compute notice period",
  "take back HR materials"), with the responsible person resolved
  dynamically — *the employee's manager*, *the employee*, or a named user.

## Comparison

| Capability | SLMS360 | Frappe | Horilla | Odoo |
|---|---|---|---|---|
| Approval gate before it takes effect | **Yes (L2)** | No (submit = done) | Resignation only | No |
| Checklist / tasks | No | Yes, templated | Yes, staged | Yes, activity plans |
| Assign task to role / manager dynamically | — | Role | Named managers | Manager / employee |
| Future-dated join/exit applied by scheduler | **Yes** | — | — | **Yes** |
| Reassign leaver's direct reports | **Yes, enforced** | No | No | No |
| Configurable departure reasons | Free text | Free text | Exit reasons | **Yes, per country** |
| Employee-initiated resignation | No | No | **Yes** | No |
| Exit interview | No | **Yes** | As a stage | No |
| Final settlement (F&F) | No | **Yes** | As a stage | No |
| New-hire self-service portal | No | No | **Yes** | No |

## Takeaways for Opsyra

1. **SLMS360's approval gate is the one thing none of them do well.**
   Keep it. Direct-report reassignment is also unique and valuable — keep it.
2. **The gap is the checklist.** All three mature systems converge on
   *template → per-person task list → status derived from tasks*. That is
   what customers expect from "onboarding". Recommended as the next layer,
   on top of the approval gate, not instead of it.
3. **Departure reasons should be a configurable list, not free text** — and
   Odoo's per-country scoping maps directly onto our four country
   deployments (e.g. statutory reason codes differ by country). Cheap to do now.
4. **Defer:** exit interviews, F&F settlement (needs Salaries hub + the
   ledger, neither built), self-service portal, employee-initiated
   resignation. Each is worth building, none is needed for the port.
5. Frappe's `required_for_employee_creation` (checklist items that block
   activation) is a neat bridge between checklist and gate — worth
   considering when the checklist layer is built.

## Outcome

The user chose **gate + departure reasons** for this module; the checklist
is the next module. Built as described in `docs/provenance.md`.

UI: the user viewed Horilla's live demo (hr.demo.horilla.com) on
2026-09-24 and found it a good enough reference for the look to aim for —
kanban-style stage pipelines, approval queues, per-person task lists.
Frappe HR's demo needs a signup and Krayin's needs a login, so neither was
viewed. No frontend stack is chosen yet (see `docs/status.md`).

## Not studied yet

Krayin (`_reference/krayin-crm`, MIT, Laravel) and OroCRM
(`_reference/oro-crm-app`, OSL-3.0, Symfony) were cloned root-only for
licence checks. Both are CRMs — Leads/CRM is excluded from scope per
Checkpoint 1 — so neither is relevant to this module. Krayin is the only
Laravel codebase of the set; its `packages/Webkul/*` modular layout and
`Automation` (workflows/webhooks) package are worth a look when we get to
cross-module concerns. Note the OroCRM clone is just the app shell — the
actual code lives in the `oro/crm` and `oro/platform` Composer packages.
