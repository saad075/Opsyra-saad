# Research: Attendance — SLMS360 vs open-source HR

Written 2026-09-26, before building the Attendance module (Phase 3). Design
reference only — no code copied (licence policy in `docs/decisions.md`).
Sources: SLMS360 @ `fa18b472`; ToddlersApp (rota); `_reference/frappe-hrms`
(`hr/doctype/{shift_type,attendance,employee_checkin,attendance_request,
leave_type,leave_application,leave_allocation,shift_assignment}`, GPL-3.0,
read from git objects); `_reference/horilla` (`attendance/models.py`,
`leave/models.py`, `base/models.py`, LGPL-2.1).

## What SLMS360 actually does

- **Shift** (`staff_attendance_shifts`, one per staff member via
  `users.attendance_shift_id`) with a row per weekday
  (`staff_attendance_shift_times`): working or day off, check-in time,
  grace window, check-out time, required minutes, an overnight flag, and
  three early-leaving thresholds (early off / short leave / early-off-absent,
  in minutes short of the required time). The shift also carries the
  **deduction policy**: lates and early-offs allowed per month, % of a day's
  pay or a fixed amount per late / early off / short leave / early-off-absent,
  and how many days' pay an absence costs. It also holds leave allowances
  (annual, medical, casual, bereavement, other).
- **A day** (`staff_attendance_dates`) has one status code out of 14:
  P present, LT late (free), LTD late (deducted), EO / EOD early off
  (free / deducted), SL short leave, EOA early-off-absent, A absent,
  L paid leave, UL unpaid leave, H holiday or day off, RW remote work,
  UD undefined, MC missed checkout. Punches (`staff_attendances`) are
  in/out pairs from the app, with branch.
- **Status rules** (in the check-in endpoint): checking in after the grace
  time makes the day late — free until the month's allowance is used, then
  deducted. At check-out, minutes worked against the day's requirement pick
  P / early off / short leave / early-off-absent. A later full check-out
  can restore P (or late).
- **Nightly job**: every active staff member with a shift gets today's row —
  A by default, H on a day off or public holiday, RW if remote; days left
  checked-in are closed as **MC (missed checkout)**, which is deducted until
  HR corrects it (overnight shifts are left alone while still running).
- **Holidays** per branch; **leave** requests split per month, approved
  **day by day** as paid (L) or unpaid (UL), through the manager hierarchy
  (parent → grandparent → great-grandparent); leave balance on the user row.
- **Attendance claims**: staff dispute a day's status; a manager approves
  through the same hierarchy and HR fixes the day.
- **Payroll** (`AttendanceDeductionCalculator`): counts each status in the
  period, per-day pay = (basic + allowances) ÷ days in period, applies the
  shift's policy, and also picks up last period's **unprocessed** days.

### Problems found (must not be ported as-is)

1. **Authorisation almost absent.** Shifts and holidays: no permission checks
   at all. Attendance: 1 check across 1,600 lines — `updateStatus` loads any
   attendance day by id and changes it. Claims approve by raw id with no
   tenant filter. Leave approval: 1 check in 927 lines.
2. **Business rules live in controllers** (check-in, leave approval), so the
   nightly job, manual marking and the mobile endpoint each re-implement
   parts of them and drift (commented-out copies, `* copy.php` files).
3. **14 status codes mix three questions** — was the person there, was it
   paid, was it deducted. "LT" vs "LTD" is a pay decision frozen into the
   attendance record at check-in time: changing the allowance later can't
   re-evaluate the month.
4. **`LIKE '%Y-m%'` date matching**, MySQL timezone switching with
   `date_default_timezone_set` inside a request, time stored as strings.
5. **Leave balance is two floats on `users`** — no allocation history, no
   per-type balances, no audit of why the number changed.

## How Frappe HRMS and Horilla model it

| Concept | Frappe HRMS | Horilla |
|---|---|---|
| Shift | `Shift Type`: start/end, grace for late entry & early exit, half-day and absent **thresholds in hours**, auto-attendance from check-ins, holiday list | `EmployeeShift` + per-day schedule, `GraceTime`, rotating shifts |
| Who works which shift | `Shift Assignment` with **date ranges** (history kept) | shift on the employee's work info; rotating assignments |
| Raw clocking | `Employee Checkin` (IN/OUT, time, device, geolocation) | `AttendanceActivity` (clock in/out pairs) |
| The day | `Attendance`: status **Present / Absent / Half Day / On Leave / Work From Home**, plus separate `late_entry` / `early_exit` flags and working hours | `Attendance` with worked hours, minimum hours, overtime, **HR validation** before it counts |
| Corrections | `Attendance Request` (on duty / WFH for a date range) | attendance request = proposed changes, approved by a manager |
| Days off | `Holiday List` (weekly offs + dated holidays), assigned per company/employee | `CompanyLeaves` (weekly offs), `Holidays` (with recurring) |
| Leave | `Leave Type` (paid/without pay, carry forward, max continuous, include holidays or not, earned monthly), `Leave Allocation` per person per period, `Leave Application` with half day and approver | `LeaveType` (payment, reset/carry forward, require approval/attachment, exclude holidays), `AvailableLeave` per type, `LeaveRequest` with half-day breakdown and multi-level approval |
| Overtime | optional on shift type | monthly hour account (`AttendanceOverTime`) with approval |
| Payroll link | days of "Absent" and leave-without-pay reduce payment days on the salary slip | worked/pending hours feed payroll |

**Ideas worth taking**

- **Keep attendance facts separate from pay rules** (Frappe): a day records
  what happened — status Present / Absent / Half day / On leave / Remote /
  Day off, plus *late* and *left early* flags and minutes worked. Whether
  that costs pay is decided **at pay-run time** by the company's policy, so
  changing the policy (e.g. 3 free lates instead of 2) applies cleanly and
  approved payslips stay untouched.
- **Shift assignment with dates** (Frappe) instead of one shift column on the
  user — history survives a shift change.
- **Weekly days off + dated holidays** as one "working calendar" (Frappe's
  holiday list, Horilla's company leaves).
- **HR review before pay** (Horilla's validation): the payslip line lists
  the days it's based on; HR fixes days before approving the run.
- **Corrections as requests** (both): staff ask for a day to be changed,
  their manager or HR approves — SLMS360's claims, with authorisation.
- **Leave as its own module** with types, per-person balances per year,
  half days, and approval (both references split it out).

## Proposed shape for Opsyra (subject to the user's decisions below)

1. **Attendance** (this module): working calendar (shifts with a pattern per
   weekday, grace, half-day/absent thresholds; public holidays by branch);
   shift assignment with dates; clock in/out from the browser (branch
   picked, timestamps in the branch's timezone); HR daily view and manual
   marking; nightly close-out (absent / day off / holiday / missed checkout);
   correction requests; an attendance dashboard; Setup wizard steps; seeded
   country defaults (e.g. Oman and Pakistan Fri/Sat-Sun weekends).
2. **Payroll link** through the existing `PayslipContributor` contract: an
   "Attendance" deduction line per payslip from the company's policy
   (absent day = one day's pay; half day = half; N free lates a month then a
   % of a day; unpaid leave = one day's pay). Off until the company turns
   it on in Setup.
3. **Leave** (next module): types, yearly allowances and balances, requests
   with half days, manager → HR approval, writing "On leave (paid/unpaid)"
   onto attendance days.
4. **Rota** (ToddlersApp's weekly rota for hourly staff) — later, as an
   extension of shifts for shift-based businesses.
