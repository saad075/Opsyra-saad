# Research: Salaries hub — SLMS360 vs open-source payroll

Written 2026-09-24, before porting the Salaries hub (Phase 3, module 5).
Design reference only — no code copied (licence policy in
`docs/decisions.md`). Sources: SLMS360 @ `fa18b472`; `_reference/frappe-hrms`
(`hrms/payroll`, GPL-3.0); `_reference/horilla` (`payroll/models/models.py`,
LGPL-2.1). **Odoo's payroll (`hr_payroll`) is Enterprise-only** — Community
has just a "structure type" label — so Odoo is not a reference here.

## What SLMS360 actually does

- **Salary record** (`user_salaries`, one row per staff member): fixed
  columns — `monthly_salary` (basic), `medical`, `house_rent`, `ta`
  (travel), `mobile`, plus `tax` (a percentage) and `tax_amount` (a fixed
  amount); `wage_basis` salaried/hourly + `hourly_rate`. Changes are logged
  field-by-field to `user_salary_logs`. No effective dates — a raise
  overwrites the old figure.
- **Pay run** (`staff:post-salary {month} {year}`, triggered from the API
  via `Artisan::call` inside the request): per active staff member,
  gross = sum of the fixed columns → minus **attendance deductions**
  (absent, unpaid leave, late, early-off, short leave, missed checkout —
  all from `StaffAttendanceDate` via `AttendanceDeductionCalculator`) →
  minus tax (flat %, pro-rated by days, or the US-federal engine) → plus
  the month's **adjustments** balance (`staff_adjustments` credits −
  debits). One `staff_salary_posts` row per person per month with ~40
  denormalised columns. Separate biweekly command for US clients.
- **Approval**: `pending` → `salary-approval-l1` or `-l2` (whichever the
  approver holds) — the same shared `HRMS-Approval-L2` permission.
  Payment method (transfer vs cheque) decided at approval from whether bank
  details exist; cheque numbers captured afterwards.
- **Regenerate** (recompute a post), **carry forward** (turn an unpaid post
  into next month's credit adjustment), **manual** (one person's post).
- **Salary claims**: staff dispute a posted salary; HR approves/rejects.
- **Bank details** (`employee_accounts`): account number, IBAN, SWIFT,
  routing number, tax id.

### Problems found (must not be ported as-is)

1. **Almost no authorisation.** Across `SalaryController` (596 lines) and
   `SalaryClaimController` there is **one** permission check (which approval
   label to stamp). Anyone logged in can: approve salaries, change anyone's
   salary, **change anyone's bank account details** (i.e. redirect their
   pay), read any salary by id, trigger a pay run.
2. **Bank details stored in plain text** — account numbers, IBAN, tax ids.
3. **`decimal(8,2)` money columns** — max 999,999.99. A Pakistani salary of
   PKR 1,000,000/month doesn't fit; annual figures overflow far sooner.
4. **Pay runs run inside the HTTP request** (`set_time_limit(0)` +
   `Artisan::call`), errors swallowed by `Log::info` + rollback, and a
   "salary generated" email sent even when that person's post failed.
5. **L1/L2 isn't a sequence** — an L2 holder's approval skips L1; an L1
   holder can re-approve an L2-approved post back down to L1.
6. **No effective dating** — history of what someone was paid when exists
   only as a field-change log.
7. The fixed allowance columns (medical/house rent/travel/mobile) are
   Pakistan-style; they don't map to UK, US or Oman pay.

## How Frappe HRMS and Horilla model it

| Concept | Frappe HRMS | Horilla |
|---|---|---|
| Pay items | **Salary Component** — Earning / Deduction / Employer contribution; fixed amount or formula; taxable flag; "depends on payment days" (pro-rate) | **Allowance** / **Deduction** — fixed or % of basic/gross; conditions; taxable/pre-tax flags; max limits |
| Per-employee pay | **Salary Structure** (template of components) + **Structure Assignment** (employee, from-date, base) | **Contract** (wage, wage type, pay frequency, start/end, notice period, leave-deduction rules) |
| One-offs | **Additional Salary** (component + amount + payroll date, optionally recurring) | one-time allowances/deductions, **Reimbursement**, **Loan** (instalments) |
| Pay run | **Payroll Entry** (period, frequency, filters) → draft **Salary Slips** → submit | **Payslip** per employee per period (JSON of pay heads), auto-generate setting |
| Payslip | earnings + deductions lines, payment/absent days, gross, total deduction, net | pay-head JSON, basic, gross, deduction, net, sent-to-employee |
| Attendance link | payment days from attendance/leave (component flag) | contract rule: deduct leave from basic pay |
| Tax | income-tax slabs per payroll period, exemption declarations | filing status + tax brackets |

**Common shape:** configurable **pay components** → a per-employee
**effective-dated salary** built from them → a **pay run** for a period
that produces **draft payslips** with itemised lines → approve/submit.
Attendance feeds in as *payment days* / a deduction line, not as dozens of
bespoke columns.

## Takeaways for Opsyra

1. **Salaries depends on Attendance** — its core deductions come from
   attendance records, which don't exist yet. Either build Attendance first,
   or build the salary foundations now with attendance deductions as a
   pluggable step (zero until Attendance exists).
2. **Replace the fixed allowance columns with configurable pay components**
   (Frappe/Horilla), seeded per country — e.g. Pakistan tenants start with
   Basic / House rent / Medical / Travel / Mobile, matching SLMS360.
3. **Effective-dated salary assignments** instead of overwrite-plus-log.
4. **Itemised payslip lines** (component, amount) instead of ~40 columns, so
   new components need no migration.
5. **Keep from SLMS360:** the shared L2 gate, adjustments, carry-forward,
   salary claims, transfer-vs-cheque payment method.
6. **Tax in this module stays simple** (a deduction component: fixed or %
   of gross). Country tax engines — US federal (its own module on the
   plan), UK PAYE, Pakistan slabs — plug in later.
7. **Fix all seven problems above**, especially authorisation, encrypted
   bank details and money column size.

## Decisions (user, 2026-09-25)

- **Salaries now; Attendance plugs in later** as extra payslip lines
  (a contributor hook that returns nothing until Attendance exists).
- **Configurable pay components**, seeded per country (Pakistan gets
  SLMS360's Basic / House rent / Medical / Travel / Mobile).
- **Monthly and biweekly** pay runs from the start.

Permissions follow SLMS360's names: `HRMS-SalaryManagement` (set up
salaries, components, bank details, run payroll), `HRMS-StaffAdjustments`
(adjustments), `HRMS-Approval-L1` / `HRMS-Approval-L2` (the shared
approval authorities; SLMS360 renamed its `HRMS-SalaryApproval-L1/L2` to
these in 2026-09-11).
