/** Shapes returned by the Leads module's API (app/Modules/Crm). */
import type { Named } from './types'

export interface LeadPipelineStage {
  id: number
  lead_pipeline_id: number
  code: string
  name: string
  probability: number
  sort_order: number
}

export interface LeadPipeline {
  id: number
  name: string
  is_default: boolean
  rotten_days: number
  stages: LeadPipelineStage[]
}

export interface LeadSource {
  id: number
  name: string
  sequence: number
  is_active: boolean
  is_system: boolean
}

export interface LeadType {
  id: number
  name: string
  sequence: number
  is_active: boolean
  is_system: boolean
}

/** Money is a string on the wire (Laravel's decimal cast) — always parse with Number() before formatting. */
export interface LeadRow {
  id: number
  title: string
  description: string | null
  lead_value: string | null
  status: boolean | null
  lost_reason: string | null
  closed_at: string | null
  expected_close_date: string | null
  contact_name: string | null
  contact_email: string | null
  contact_phone: string | null
  lead_source_id: number | null
  lead_type_id: number | null
  lead_pipeline_id: number | null
  lead_pipeline_stage_id: number | null
  owner_id: number | null
  source?: Named | null
  type?: Named | null
  pipeline?: Named | null
  stage?: Pick<LeadPipelineStage, 'id' | 'name' | 'code'> | null
  owner?: Named | null
  is_rotten?: boolean
  /** All only present on /leads/show, not /leads/list or /leads/move's response. */
  stage_changes?: LeadStageChange[]
  notes?: LeadNote[]
  activities?: LeadActivity[]
  files?: LeadFile[]
  emails?: LeadEmail[]
}

export interface LeadStageChange {
  id: number
  created_at: string
  from_stage: Pick<LeadPipelineStage, 'id' | 'name' | 'code'> | null
  to_stage: Pick<LeadPipelineStage, 'id' | 'name' | 'code'> | null
  changed_by: Named | null
}

export interface LeadNote {
  id: number
  body: string
  created_at: string
  author: Named | null
}

export type LeadActivityType = 'call' | 'meeting' | 'lunch' | 'deadline' | 'other'

export interface LeadActivity {
  id: number
  type: LeadActivityType
  title: string
  comment: string | null
  scheduled_at: string | null
  is_done: boolean
  created_at: string
  author: Named | null
}

export interface LeadFile {
  id: number
  original_name: string
  mime_type: string
  size: number
  created_at: string
  uploader: Named | null
}

export interface LeadEmail {
  id: number
  to_email: string
  subject: string
  body: string
  created_at: string
  sender: Named | null
}

export interface LeadForm {
  title: string
  description: string | null
  lead_value: number | null
  expected_close_date: string | null
  contact_name: string | null
  contact_email: string | null
  contact_phone: string | null
  lead_source_id: number | null
  lead_type_id: number | null
  owner_id: number | null
}
