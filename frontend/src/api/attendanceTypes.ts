/** Shapes returned by the Attendance module's API (app/Modules/Attendance). */
import type { Named } from './types'

export type DayStatus = 'present' | 'half_day' | 'absent' | 'on_leave' | 'remote' | 'day_off' | 'holiday' | 'open'

export interface Punch {
  id: number
  type: 'in' | 'out'
  at: string
  branch: string | null
  distance_m: number | null
  within_range: boolean | null
  accuracy_m: number | null
  source: string
}

export interface AttendanceDayRecord {
  id: number
  user_id: number
  date: string
  status: DayStatus
  leave_paid: boolean | null
  expected: string | null
  scheduled_minutes: number
  worked_minutes: number
  is_late: boolean
  late_minutes: number
  left_early: boolean
  early_minutes: number
  missed_checkout: boolean
  first_in_at: string | null
  last_out_at: string | null
  is_manual: boolean
  source: string
  note: string | null
  needs_review: boolean
  punches?: Punch[]
}

export interface Expectation {
  kind: 'work' | 'day_off' | 'holiday' | 'visits' | 'not_tracked'
  date: string
  timezone: string
  schedule: Named | null
  start: string | null
  end: string | null
  required_minutes: number
  holiday: string | null
}

export interface AttendanceSummary {
  present: number
  half_day: number
  absent: number
  on_leave: number
  remote: number
  day_off: number
  holiday: number
  late: number
  left_early: number
  missed_checkout: number
  worked_minutes: number
}

export interface DailySheet {
  date: string
  summary: { expected: number; in: number; late: number; absent: number; on_leave: number; not_in_yet: number; needs_review: number }
  rows: { staff: { id: number; name: string; designation: string | null; attendance_mode: string }; expected: Expectation; day: AttendanceDayRecord | null }[]
}

export interface PersonMonth {
  staff: { id: number; name: string; designation: string | null; attendance_mode: string }
  month: string
  timezone: string
  summary: AttendanceSummary
  days: AttendanceDayRecord[]
}

export interface MyToday {
  date: string
  timezone: string
  attendance_mode: 'schedule' | 'visits' | 'not_tracked'
  expected: Expectation
  day: AttendanceDayRecord | null
  next_action: 'in' | 'out'
  branches: { id: number; name: string; latitude: number | null; longitude: number | null; geofence_radius_m: number | null }[]
}

export interface AttendanceCorrectionRow {
  id: number
  user_id: number
  date: string
  requested_in: string | null
  requested_out: string | null
  requested_status: 'remote' | 'on_leave' | null
  reason: string
  status: 'open' | 'approved' | 'rejected'
  response: string | null
  resolved_at: string | null
  staff?: { id: number; name: string; designation: string | null }
  resolved_by?: Named | null
}

export interface WorkScheduleDayRow {
  weekday: number
  is_working: boolean
  start_time: string | null
  end_time: string | null
  break_minutes: number
}

export interface WorkScheduleRow {
  id: number
  name: string
  description: string | null
  grace_minutes: number
  early_leave_grace_minutes: number
  is_default: boolean
  is_active: boolean
  staff_count: number
  days: WorkScheduleDayRow[]
}

export interface ScheduledPerson {
  id: number
  name: string
  designation: string | null
  attendance_mode: 'schedule' | 'visits' | 'not_tracked'
  is_remote: boolean
  status: string
  schedule: Named | null
  on_default: boolean
  since: string | null
}

export interface HolidayRow {
  id: number
  date: string
  name: string
  branch_id: number | null
  branch: Named | null
}

export interface AttendancePolicyValues {
  half_day_below_percent: number
  absent_below_percent: number
  deductions_enabled: boolean
  absent_deduction_days: number
  half_day_deduction_days: number
  unpaid_leave_deduction_days: number
  free_lates_per_month: number
  late_deduction_percent: number
  free_early_leaves_per_month: number
  early_leave_deduction_percent: number
  missed_checkout_counts_as: 'present' | 'half_day' | 'absent'
}

export interface AttendanceDashboardData {
  trend: { date: string; present: number; absent: number; on_leave: number; late: number }[]
  month: {
    attendance_rate: number | null
    lates: number
    absences: number
    most_late: { user_id: number; name: string; count: number }[]
    most_absent: { user_id: number; name: string; count: number }[]
  }
  waiting: { corrections: number; far_clock_ins: number; missed_checkouts: number; visits_to_review: number }
}
