/** Shapes returned by the Opsyra API — see the matching Laravel controllers. */

export interface ApiEnvelope<T> {
  status: boolean
  message: string
  data: T
}

export interface Me {
  user: { id: number; name: string; email: string; designation: string | null; avatar: string | null; status: string }
  must_change_password: boolean
  company: { name: string; client_code: string; country: string; is_demo: boolean }
  roles: string[]
  permissions: string[]
}

export type SetupStepStatus = 'done' | 'skipped' | 'pending'

export interface SetupStep {
  key: string
  group: string
  group_label: string
  title: string
  description: string
  kind: 'data' | 'review'
  required: boolean
  status: SetupStepStatus
  detail: string | null
}

export interface SetupStatus {
  steps: SetupStep[]
  completed_count: number
  total_count: number
  next_step: string | null
  is_complete: boolean
}

export interface CompanySettings {
  company_name: string | null
  address: string | null
  city: string | null
  phone: string | null
  email: string | null
  timezone: string | null
  currency: string | null
  country: string
}

export interface Branch {
  id: number
  name: string
  status: boolean
  address: string | null
  city: string | null
  phone: string | null
  timezone: string | null
  staff_count: number
}

export interface LabelValue {
  label: string
  value: number
}

export interface UpcomingPerson {
  id: number
  name: string
  designation: string | null
  date: string
}

export interface HrDashboard {
  headcount: { active: number; starting_soon: number; awaiting_approval: number; joined_this_month: number; left_this_month: number }
  trend: LabelValue[]
  by_branch: LabelValue[]
  by_designation: LabelValue[]
  approvals: { onboarding: number; offboarding: number; rejected_awaiting_notice: number }
  checklists: { in_progress: number; overdue_tasks: number; completed_this_month: number }
  upcoming_joiners: UpcomingPerson[]
  upcoming_leavers: UpcomingPerson[]
}

export interface RunTotals {
  currency: string
  gross: number
  deductions: number
  net: number
}

export interface PayrollDashboard {
  awaiting: { drafts: number; level_2: number; for_me: number; cheques: number; claims: number }
  coverage: { active_staff: number; with_salary: number; with_bank_details: number }
  runs: {
    id: number
    frequency: string
    period_start: string
    period_end: string
    status: string
    payslips: number
    approved: number
    failures: number
    totals: RunTotals[]
  }[]
  latest_breakdown: { label: string; type: 'earning' | 'deduction'; amount: number }[]
}
