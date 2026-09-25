# Research: Setup wizard

Written 2026-09-25. The user moved the Setup wizard up the plan: with the
configurable (Frappe/Horilla-style) approach, a company admin must be
walked through configuring everything at initial setup, with progress
shown and steps skippable (see `docs/status.md`). Design reference only —
no code copied.

## What each one does

- **SLMS360** (`Client/SetupWizardController.php`, 73 lines +
  `SetupWizard.vue`): five steps, each marked done by checking whether the
  data exists (organisation name, a branch, classes, a staff member,
  students), never by a stored flag, so it can't drift from reality. The
  page shows "N of M complete", a progress bar, and a Set up / Review
  button per step. Classes and students are school-specific: dropped.
- **Horilla** (`base/dashboard.py` + `setup_checklist_banner.html`): the
  same live-computed idea, with 8 steps (company, department, job position,
  shifts, work type, email configuration, …), shown only to admins,
  highlighting the next step, dismissible per user per company.
- **Frappe HRMS** (`setup_wizard.py`): a first-login form sequence
  (country, currency, timezone, company) that creates the records, plus
  module "onboarding" step lists.

## Design for Opsyra

- **Live-computed status**, as in SLMS360 and Horilla.
- **Steps come from each module** through a registry (`SetupStepProvider`,
  container tag `setup.step_providers`). A module that adds per-company
  configuration registers its own steps, so none gets forgotten.
- **Two kinds of step.** *Data* steps are done when the data exists (a
  branch, a department, every active employee has a salary). *Review*
  steps cover things Opsyra pre-fills from country defaults (leaving
  reasons, checklist templates, pay components); these are done when the
  admin confirms they've reviewed them.
- **Skip and come back:** any step can be skipped or un-skipped. That
  state is stored per tenant; completion never is.
- **Company profile settings** (name, address, phone, email, timezone,
  currency) in a per-tenant key/value table. The timezone is stored now so
  that the "today is UTC for every tenant" gap can be fixed against it.
- **Branch management** is built here: the wizard needs it, and it was a
  known gap from the HR core module. Branches gain address, phone and
  timezone (SLMS360's branches had a timezone).
