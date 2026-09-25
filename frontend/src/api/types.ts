/** Shapes returned by the Opsyra API — see the matching Laravel controllers. */

export interface ApiEnvelope<T> {
  status: boolean
  message: string
  data: T
}

export interface Me {
  user: { id: number; name: string; email: string; designation: string | null; avatar: string | null; status: string }
  must_change_password: boolean
  company: { name: string; client_code: string; country: string }
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
