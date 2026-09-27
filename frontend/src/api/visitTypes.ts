/** Shapes returned by the visits API (home care — app/Modules/Attendance). */
import type { Named } from './types'

export type VisitStatus = 'planned' | 'in_progress' | 'completed' | 'missed' | 'cancelled'

export interface VisitLocationRow {
  id: number
  name: string
  reference: string | null
  address: string
  city: string | null
  postcode: string | null
  latitude: number | null
  longitude: number | null
  geofence_radius_m: number
  access_notes: string | null
  is_active: boolean
  branch_id: number | null
  branch?: Named | null
  upcoming_visits_count?: number
}

export interface VisitRow {
  id: number
  date: string
  status: VisitStatus
  planned_start: string
  planned_end: string
  planned_minutes: number
  location: Omit<VisitLocationRow, 'reference' | 'is_active' | 'branch_id' | 'branch' | 'upcoming_visits_count'> | null
  staff: Named | null
  from_plan: boolean
  checked_in_at: string | null
  check_in_latitude: number | null
  check_in_longitude: number | null
  check_in_distance_m: number | null
  check_in_within_range: boolean | null
  checked_out_at: string | null
  check_out_latitude: number | null
  check_out_longitude: number | null
  check_out_distance_m: number | null
  check_out_within_range: boolean | null
  missed_checkout: boolean
  worked_minutes: number
  countable_minutes: number
  review_status: 'needs_review' | 'accepted' | 'rejected' | null
  review_note: string | null
  caregiver_note: string | null
  cancel_reason: string | null
}

export interface VisitBoard {
  date: string
  summary: { planned: number; completed: number; in_progress: number; missed: number; needs_review: number }
  caregivers: { id: number; name: string; designation: string | null; visits: VisitRow[] }[]
}

export interface VisitPlanRow {
  id: number
  visit_location_id: number
  user_id: number
  weekdays: number[]
  start_time: string
  end_time: string
  starts_on: string
  ends_on: string | null
  notes: string | null
  location?: { id: number; name: string; address: string }
  staff?: Named
}
