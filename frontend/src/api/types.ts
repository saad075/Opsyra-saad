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

export interface StaffMember {
  id: number
  name: string
  email: string
  status: 'active' | 'pending' | 'pending_approval' | 'offboarded' | string
  designation: string | null
  phone: string | null
  dob: string | null
  joined_at: string | null
  lefted_at: string | null
  left_reason: string | null
  is_remote: boolean
  hierarchy_parent_id: number | null
  national_id: string | null
  blood_group: string | null
  address: string | null
  city: string | null
  zip_code: string | null
  avatar: string | null
  branches: { branch_id: number; status: boolean; branch: { id: number; name: string } | null }[]
  roles: { id: number; name: string }[]
}

export interface PersonRef {
  id: number
  name: string
  designation?: string | null
  status?: string
}

export interface OnboardingRequest {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  rejection_reason: string | null
  reviewed_at: string | null
  completed_at: string | null
  created_at: string
  staff?: PersonRef & { joined_at?: string | null }
  requested_by?: PersonRef | null
  reviewed_by?: PersonRef | null
}

export interface OffboardRequest {
  id: number
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  last_working_day: string
  note: string | null
  rejection_reason: string | null
  direct_reports_count: number
  created_at: string
  staff?: PersonRef
  requested_by?: PersonRef | null
  reviewed_by?: PersonRef | null
  new_manager?: PersonRef | null
  departure_reason?: { id: number; name: string } | null
}

export interface StaffProfile {
  staff: StaffMember
  manager: PersonRef | null
  direct_reports: PersonRef[]
  onboarding: OnboardingRequest | null
  offboarding: OffboardRequest | null
  checklists: { id: number; type: 'onboarding' | 'offboarding'; status: string; anchor_date: string; tasks_count: number; closed_tasks_count: number }[]
}

export interface Named {
  id: number
  name: string
}

export type TaskStatus = 'todo' | 'in_progress' | 'done' | 'skipped'

export interface ChecklistTask {
  id: number
  staff_checklist_id: number
  title: string
  description: string | null
  assignee_user_id: number | null
  assignee_role_id: number | null
  due_date: string | null
  is_required: boolean
  status: TaskStatus
  note: string | null
  completed_at: string | null
  assignee_user?: Named | null
  assignee_role?: Named | null
  completed_by?: Named | null
  checklist?: { id: number; user_id: number; type: 'onboarding' | 'offboarding'; anchor_date: string; staff: PersonRef & { avatar: string | null } }
}

export interface Checklist {
  id: number
  user_id: number
  type: 'onboarding' | 'offboarding'
  anchor_date: string
  status: 'in_progress' | 'completed' | 'cancelled'
  completed_at: string | null
  staff: PersonRef & { avatar: string | null }
  template: Named | null
  tasks?: ChecklistTask[]
  tasks_count?: number
  closed_tasks_count?: number
  overdue_tasks_count?: number
}
